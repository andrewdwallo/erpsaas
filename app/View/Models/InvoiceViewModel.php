<?php

namespace App\View\Models;

use App\Models\Setting\DocumentDefault;
use Spatie\ViewModels\ViewModel;

class InvoiceViewModel extends ViewModel
{
    public DocumentDefault $invoice;

    public ?array $data = [];

    public function __construct(DocumentDefault $invoice, ?array $data = null)
    {
        $this->invoice = $invoice;
        $this->data = $data;
    }

    public function document_logo(): ?string
    {
        return $this->data['document_logo'] ?? $this->invoice->document_logo ?? $this->invoice->company->logo ?? null;
    }

    // Company related methods
    public function company_name(): string
    {
        return $this->invoice->company->name;
    }

    public function company_address(): ?string
    {
        return $this->invoice->company->address ?? null;
    }

    public function company_phone(): ?string
    {
        return $this->invoice->company->phone ?? null;
    }

    public function company_city(): ?string
    {
        return $this->invoice->company->city ?? null;
    }

    public function company_state(): ?string
    {
        return $this->invoice->company->state ?? null;
    }

    public function company_zip(): ?string
    {
        return $this->invoice->company->zip_code ?? null;
    }

    // Invoice numbering related methods
    public function number_prefix(): string
    {
        return $this->data['number_prefix'] ?? $this->invoice->number_prefix ?? 'INV-';
    }

    public function number_digits(): int
    {
        return $this->data['number_digits'] ?? $this->invoice->number_digits ?? $this->invoice->getDefaultNumberDigits();
    }

    public function number_next(): int
    {
        return $this->data['number_next'] ?? $this->invoice->number_next ?? $this->invoice->getNextDocumentNumber($this->number_digits());
    }

    public function invoice_number(): string
    {
        return $this->number_prefix() . str_pad($this->number_next(), $this->number_digits(), "0", STR_PAD_LEFT);
    }

    // Invoice date related methods
    public function invoice_date(): string
    {
        return now()->format('M d, Y');
    }

    public function payment_terms(): string
    {
        return $this->data['payment_terms'] ?? $this->invoice->payment_terms ?? $this->invoice->getDefaultPaymentTerms();
    }

    public function invoice_due_date(): string
    {
        return now()->addDays($this->payment_terms())->format('M d, Y');
    }

    // Invoice header related methods
    public function title(): string
    {
        return $this->data['title'] ?? $this->invoice->title ?? 'Invoice';
    }

    public function subheading(): ?string
    {
        return $this->data['subheading'] ?? $this->invoice->subheading ?? null;
    }

    // Invoice styling
    public function accent_color(): string
    {
        return $this->data['accent_color'] ?? $this->invoice->accent_color ?? '#6366F1';
    }

    public function footer(): string
    {
        return $this->data['footer'] ?? $this->invoice->footer ?? 'Thank you for your business!';
    }

    public function terms(): string
    {
        return $this->data['terms'] ?? $this->invoice->terms ?? 'Payment is due within thirty (30) days from the date of invoice. Any discrepancies should be reported within fourteen (14) days of receipt.';
    }

    // Invoice column related methods
    public function item_column(): string
    {
        $item_column = $this->data['item_column'] ?? $this->invoice->item_column ?? $this->invoice->getDefaultItemColumn();
        return isset($this->invoice->getItemColumns()[$item_column]) ? ucfirst($item_column) : $item_column;
    }

    public function unit_column(): string
    {
        $unit_column = $this->data['unit_column'] ?? $this->invoice->unit_column ?? $this->invoice->getDefaultUnitColumn();
        return isset($this->invoice->getUnitColumns()[$unit_column]) ? ucfirst($unit_column) : $unit_column;
    }

    public function price_column(): string
    {
        $price_column = $this->data['price_column'] ?? $this->invoice->price_column ?? $this->invoice->getDefaultPriceColumn();
        return isset($this->invoice->getPriceColumns()[$price_column]) ? ucfirst($price_column) : $price_column;
    }

    public function amount_column(): string
    {
        $amount_column = $this->data['amount_column'] ?? $this->invoice->amount_column ?? $this->invoice->getDefaultAmountColumn();
        return isset($this->invoice->getAmountColumns()[$amount_column]) ? ucfirst($amount_column) : $amount_column;
    }

    public function buildViewData(): array
    {
        return [
            'document_logo' => $this->document_logo(),
            'company_name' => $this->company_name(),
            'company_address' => $this->company_address(),
            'company_phone' => $this->company_phone(),
            'company_city' => $this->company_city(),
            'company_state' => $this->company_state(),
            'company_zip' => $this->company_zip(),
            'number_prefix' => $this->number_prefix(),
            'number_digits' => $this->number_digits(),
            'number_next' => $this->number_next(),
            'invoice_number' => $this->invoice_number(),
            'invoice_date' => $this->invoice_date(),
            'payment_terms' => $this->payment_terms(),
            'invoice_due_date' => $this->invoice_due_date(),
            'title' => $this->title(),
            'subheading' => $this->subheading(),
            'accent_color' => $this->accent_color(),
            'footer' => $this->footer(),
            'terms' => $this->terms(),
            'item_column' => $this->item_column(),
            'unit_column' => $this->unit_column(),
            'price_column' => $this->price_column(),
            'amount_column' => $this->amount_column(),
        ];
    }
}
