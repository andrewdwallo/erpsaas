<?php

namespace App\Http\Controllers;

use App\DTO\DocumentDTO;
use App\Enums\Accounting\DocumentType;
use App\Enums\Setting\Template;
use App\Models\Accounting\Estimate;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\RecurringInvoice;
use App\Models\Setting\DocumentDefault;
use Illuminate\Http\Request;

class DocumentPrintController extends Controller
{
    protected array $documentModels = [
        'invoice' => Invoice::class,
        'recurring_invoice' => RecurringInvoice::class,
        'estimate' => Estimate::class,
    ];

    public function show(Request $request, string $documentType, int $id)
    {
        if (! isset($this->documentModels[$documentType])) {
            abort(404, "Invalid document type: {$documentType}");
        }

        $modelClass = $this->documentModels[$documentType];
        $docModel = $modelClass::findOrFail($id);
        $documentTypeEnum = $docModel::documentType();

        if ($documentTypeEnum === DocumentType::RecurringInvoice) {
            $documentTypeEnum = DocumentType::Invoice;
        }

        $defaults = DocumentDefault::query()
            ->type($documentTypeEnum)
            ->first();

        $template = $defaults?->template ?? Template::Default;

        $qrBillHtml = null;
        if ($documentTypeEnum === DocumentType::Invoice) {
            // Only for invoices generate QR payment part
            $qrBillHtml = app(\App\Services\Billing\QrBillBuilder::class)->renderHtmlForInvoice($docModel);
        }

        $docDto = DocumentDTO::fromModel($docModel);

        return view('print-document', [
            'document' => $docDto,
            'template' => $template,
            'qrBillHtml' => $qrBillHtml,
        ]);
    }

    public function qrPaymentSlip(Request $request, int $id)
    {
        $invoice = Invoice::findOrFail($id);
        
        $qrBillHtml = app(\App\Services\Billing\QrBillBuilder::class)->renderHtmlForInvoice($invoice);
        
        if (! $qrBillHtml) {
            abort(404, 'QR Payment Slip not available for this invoice');
        }

        return view('qr-payment-slip', [
            'qrBillHtml' => $qrBillHtml,
            'invoice' => $invoice,
        ]);
    }
    
    public function qrPaymentSlipRecurring(Request $request, int $id)
    {
        $recurringInvoice = RecurringInvoice::findOrFail($id);
        
        $qrBillHtml = app(\App\Services\Billing\QrBillBuilder::class)->renderHtmlForInvoice($recurringInvoice);
        
        if (! $qrBillHtml) {
            abort(404, 'QR Payment Slip not available for this recurring invoice');
        }

        return view('qr-payment-slip', [
            'qrBillHtml' => $qrBillHtml,
            'invoice' => $recurringInvoice, // Keep same variable name for view compatibility
        ]);
    }
}
