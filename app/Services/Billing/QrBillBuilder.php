<?php

namespace App\Services\Billing;

use App\Models\Accounting\Document;
use App\Models\Accounting\Invoice;
use App\Models\Setting\CompanyProfile;
use Sprain\SwissQrBill\QrBill;
use Sprain\SwissQrBill\DataGroup\Element\AdditionalInformation;
use Sprain\SwissQrBill\DataGroup\Element\AlternativeScheme;
use Sprain\SwissQrBill\DataGroup\Element\PaymentReference;
use Sprain\SwissQrBill\DataGroup\Element\CreditorInformation;
use Sprain\SwissQrBill\DataGroup\Element\PaymentAmountInformation;
use Sprain\SwissQrBill\DataGroup\Element\StructuredAddress;
use Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\HtmlOutput;
use Sprain\SwissQrBill\Reference\QrPaymentReferenceGenerator;
use kmukku\Iso11649\Generator as Iso11649Generator;

class QrBillBuilder
{
    public function renderHtmlForInvoice(Document $document): ?string
    {
        $company = $document->company;
        $profile = $company->profile;

        if (! $this->isEnabledAndEligible($document, $profile)) {
            return null;
        }

        $currency = $document->currency_code;
        $amount = max(0, (int) $document->amountDue());
        if ($amount <= 0) {
            return null;
        }

        $qrBill = QrBill::create();

        // Creditor
        $qrBill->setCreditor(
            StructuredAddress::createWithStreet(
                $company->name,
                $profile->address?->address_line_1 ?? '',
                $profile->address?->address_line_2 ?? '',
                $profile->address?->postal_code ?? '',
                $profile->address?->city ?? '',
                strtoupper($profile->address?->country?->id ?? 'CH')
            )
        );

        $qrBill->setCreditorInformation(
            CreditorInformation::create(
                $this->getAccount($profile)
            )
        );

        // Ultimate Debtor (client) - only if complete billing address is available
        $client = $document->client;
        if ($client && $client->billingAddress && !$client->billingAddress->isIncomplete()) {
            $qrBill->setUltimateDebtor(
                StructuredAddress::createWithStreet(
                    $client->name,
                    $client->billingAddress->address_line_1 ?? '',
                    $client->billingAddress->address_line_2 ?? '',
                    $client->billingAddress->postal_code ?? '',
                    $client->billingAddress->city ?? '',
                    strtoupper($client->billingAddress->country?->id ?? 'CH')
                )
            );
        }

        // Amount/Currency
        $qrBill->setPaymentAmountInformation(
            PaymentAmountInformation::create(
                strtoupper($currency),
                $amount / 100 // convert cents to base unit
            )
        );

        // Reference
        [$referenceType, $referenceValue] = $this->resolveReference($document, $profile);
        $qrBill->setPaymentReference(
            PaymentReference::create(
                $referenceType === 'QRR' ? PaymentReference::TYPE_QR : ($referenceType === 'SCOR' ? PaymentReference::TYPE_SCOR : PaymentReference::TYPE_NON),
                $referenceValue ?: null
            )
        );

        // Additional info
        $unstructured = $this->getUnstructuredMessage($document, $profile);
        if ($unstructured) {
            $qrBill->setAdditionalInformation(
                AdditionalInformation::create($unstructured)
            );
        }

        // Validate before rendering
        if (!$qrBill->isValid()) {
            \Log::error('QR Bill validation failed', [
                'document_id' => $document->id,
                'document_type' => get_class($document),
                'violations' => array_map(fn($v) => $v->getMessage(), iterator_to_array($qrBill->getViolations()))
            ]);
            
            // Return null if validation fails
            return null;
        }

        try {
            // Language
            $language = $company->locale?->language ?: 'en';
            $output = new HtmlOutput($qrBill, $language);

            // Full payment part including receipt
            return $output->getPaymentPart();
        } catch (\Exception $e) {
            \Log::error('QR Bill generation failed', [
                'document_id' => $document->id,
                'document_type' => get_class($document),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function isEnabledAndEligible(Document $document, CompanyProfile $profile): bool
    {
        if (! $profile->qr_bill_enabled) {
            return false;
        }

        $currency = strtoupper($document->currency_code ?? '');
        if (! in_array($currency, ['CHF', 'EUR'], true)) {
            return false;
        }

        return true;
    }

    protected function getAccount(CompanyProfile $profile): string
    {
        return $profile->qr_bill_iban ?? '';
    }
    
    protected function isQrIban(string $iban): bool
    {
        // QR-IBAN has clearing number range 30000-31999 (positions 5-9)
        if (strlen($iban) < 9) {
            return false;
        }
        $clearingNumber = (int) substr($iban, 4, 5);
        return $clearingNumber >= 30000 && $clearingNumber <= 31999;
    }

    protected function resolveReference(Document $document, CompanyProfile $profile): array
    {
        // If stored on document (only invoices have payment_reference), reuse
        if ($document instanceof Invoice && ! empty($document->payment_reference)) {
            // Determine type based on profile mode and IBAN
            $type = $this->determineReferenceType($profile);
            return [$type, $document->payment_reference];
        }

        $iban = $profile->qr_bill_iban ?? '';
        if (empty($iban)) {
            return ['NON', ''];
        }
        
        $mode = $profile->qr_bill_mode ?? 'iban';
        
        if ($mode === 'iban') {
            // IBAN mode: No reference, use custom message from invoice if available
            return ['NON', ''];
        } else {
            // QR-IBAN mode: Generate QR reference based on pattern
            if (!$this->isQrIban($iban)) {
                \Log::warning('QR-IBAN mode selected but provided IBAN is not a QR-IBAN', [
                    'document_id' => $document->id,
                    'document_type' => get_class($document),
                    'iban' => $iban
                ]);
                return ['NON', ''];
            }
            
            $referenceString = $this->generateReferenceFromPattern($document, $profile);
            $reference = QrPaymentReferenceGenerator::generate(null, $referenceString);
            return ['QRR', $reference];
        }
    }
    
    protected function determineReferenceType(CompanyProfile $profile): string
    {
        $mode = $profile->qr_bill_mode ?? 'iban';
        
        if ($mode === 'qr_iban' && $this->isQrIban($profile->qr_bill_iban ?? '')) {
            return 'QRR';
        }
        
        return 'NON';
    }
    
    protected function generateReferenceFromPattern(Document $document, CompanyProfile $profile): string
    {
        $pattern = $profile->qr_bill_reference_pattern ?? '{document_id}';
        
        // Available placeholders - extract only numbers for QR reference
        $placeholders = [
            '{document_id}' => (string) $document->id,
            '{invoice_id}' => (string) $document->id, // Alias for backward compatibility
            '{invoice_number}' => $document instanceof Invoice ? preg_replace('/[^0-9]/', '', $document->invoice_number ?? '') : '',
            '{account_number}' => preg_replace('/[^0-9]/', '', $document->client?->account_number ?? ''),
            '{client_name}' => '', // Not suitable for QR reference (letters not allowed)
            '{date_y}' => $this->getDocumentDate($document)->format('Y'),
            '{date_m}' => $this->getDocumentDate($document)->format('m'),
            '{date_d}' => $this->getDocumentDate($document)->format('d'),
        ];
        
        // Replace placeholders in pattern
        $referenceString = str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $pattern
        );
        
        // Ensure the reference string is purely numeric
        $referenceString = preg_replace('/[^0-9]/', '', $referenceString);
        
        // Pad or truncate to fit QR reference format (max ~25 chars before checksum)
        $referenceString = str_pad(substr($referenceString, 0, 25), 12, '0', STR_PAD_LEFT);
        
        return $referenceString;
    }
    
    protected function getUnstructuredMessage(Document $document, CompanyProfile $profile): ?string
    {
        $mode = $profile->qr_bill_mode ?? 'iban';
        
        if ($mode === 'iban') {
            // IBAN mode: Use document-specific custom message (only available for Invoices)
            return $document instanceof Invoice ? ($document->qr_custom_message ?: null) : null;
        } else {
            // QR-IBAN mode: No unstructured message (reference handles identification)
            return null;
        }
    }
    
    protected function getDocumentDate(Document $document): \Carbon\Carbon
    {
        if ($document instanceof Invoice && $document->date) {
            return $document->date;
        } elseif ($document instanceof \App\Models\Accounting\RecurringInvoice && $document->start_date) {
            return $document->start_date;
        }
        
        return now(); // Fallback to current date
    }

}
