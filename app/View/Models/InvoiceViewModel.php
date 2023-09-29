<?php

namespace App\View\Models;

use App\Enums\{Font, PaymentTerms};
use App\Models\Setting\DocumentDefault;
use Filament\Panel\Concerns\HasFont;

class InvoiceViewModel
{
    use HasFont;

    public DocumentDefault $invoice;

    public ?array $data = [];

    public function __construct(DocumentDefault $invoice, ?array $data = null)
    {
        $this->invoice = $invoice;
        $this->data = $data;
    }

    public function logo(): ?string
    {
        return $this->invoice->logo ?? null;
    }

    public function show_logo(): bool
    {
        return $this->data['show_logo'] ?? $this->invoice->show_logo ?? false;
    }

    // Company related methods
    public function company_name(): string
    {
        return $this->invoice->company->name;
    }

    public function company_address(): ?string
    {
        return $this->invoice->company->profile->address ?? null;
    }

    public function company_phone(): ?string
    {
        return $this->invoice->company->profile->phone_number ?? null;
    }

    public function company_city(): ?string
    {
        return $this->invoice->company->profile->city ?? null;
    }

    public function company_state(): ?string
    {
        return $this->invoice->company->profile->state ?? null;
    }

    public function company_zip(): ?string
    {
        return $this->invoice->company->profile->zip_code ?? null;
    }

    public function company_country(): ?string
    {
        return $this->invoice->company->profile->getCountryName();
    }

    // Invoice numbering related methods
    public function number_prefix(): string
    {
        return $this->data['number_prefix'] ?? $this->invoice->number_prefix ?? 'INV-';
    }

    public function number_digits(): int
    {
        return $this->data['number_digits'] ?? $this->invoice->number_digits ?? 5;
    }

    public function number_next(): string
    {
        return $this->data['number_next'] ?? $this->invoice->number_next;
    }

    public function invoice_number(): string
    {
        return DocumentDefault::getNumberNext(padded: true, format: true, prefix: $this->number_prefix(), digits: $this->number_digits(), next: $this->number_next());
    }

    // Invoice date related methods
    public function invoice_date(): string
    {
        return now()->format('M d, Y');
    }

    public function payment_terms(): string
    {
        return $this->data['payment_terms'] ?? $this->invoice->payment_terms ?? PaymentTerms::DEFAULT;
    }

    public function invoice_due_date(): string
    {
        $enumPaymentTerms = PaymentTerms::tryFrom($this->payment_terms());
        $days = $enumPaymentTerms ? $enumPaymentTerms->getDays() : 0;

        return now()->addDays($days)->format('M d, Y');
    }

    // Invoice header related methods
    public function header(): string
    {
        return $this->data['header'] ?? $this->invoice->header ?? 'Invoice';
    }

    public function subheader(): ?string
    {
        return $this->data['subheader'] ?? $this->invoice->subheader ?? null;
    }

    // Invoice styling
    public function accent_color(): string
    {
        return $this->data['accent_color'] ?? $this->invoice->accent_color;
    }

    public function fontFamily(): string
    {
        if ($this->data['font']) {
            return Font::from($this->data['font'])->getLabel();
        }

        if ($this->invoice->font) {
            return $this->invoice->font->getLabel();
        }

        return Font::from(Font::DEFAULT)->getLabel();
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
    public function item_name(): string
    {
        $custom_item_name = $this->data['item_name']['custom'] ?? null;

        if ($custom_item_name) {
            return $custom_item_name;
        }

        return ucwords($this->data['item_name']['option']) ?? ucwords($this->invoice->item_name_option) ?? $this->invoice->item_name_custom ?? 'Items';
    }

    public function unit_name(): string
    {
        $custom_unit_name = $this->data['unit_name']['custom'] ?? null;

        if ($custom_unit_name) {
            return $custom_unit_name;
        }

        return ucwords($this->data['unit_name']['option']) ?? ucwords($this->invoice->unit_name_option) ?? $this->invoice->unit_name_custom ?? 'Quantity';
    }

    public function price_name(): string
    {
        $custom_price_name = $this->data['price_name']['custom'] ?? null;

        if ($custom_price_name) {
            return $custom_price_name;
        }

        return ucwords($this->data['price_name']['option']) ?? ucwords($this->invoice->price_name_option) ?? $this->invoice->price_name_custom ?? 'Price';
    }

    public function amount_name(): string
    {
        $custom_amount_name = $this->data['amount_name']['custom'] ?? $this->invoice->amount_name_custom ?? null;

        if ($custom_amount_name) {
            return $custom_amount_name;
        }

        return ucwords($this->data['amount_name']['option']) ?? ucwords($this->invoice->amount_name_option) ?? 'Amount';
    }

    public function buildViewData(): array
    {
        return [
            'logo' => $this->logo(),
            'show_logo' => $this->show_logo(),
            'company_name' => $this->company_name(),
            'company_address' => $this->company_address(),
            'company_phone' => $this->company_phone(),
            'company_city' => $this->company_city(),
            'company_state' => $this->company_state(),
            'company_zip' => $this->company_zip(),
            'company_country' => $this->company_country(),
            'number_prefix' => $this->number_prefix(),
            'number_digits' => $this->number_digits(),
            'number_next' => $this->number_next(),
            'invoice_number' => $this->invoice_number(),
            'invoice_date' => $this->invoice_date(),
            'payment_terms' => $this->payment_terms(),
            'invoice_due_date' => $this->invoice_due_date(),
            'header' => $this->header(),
            'subheader' => $this->subheader(),
            'accent_color' => $this->accent_color(),
            'font_family' => $this->fontFamily(),
            'font_html' => $this->font($this->fontFamily())->getFontHtml(),
            'footer' => $this->footer(),
            'terms' => $this->terms(),
            'item_name' => $this->item_name(),
            'unit_name' => $this->unit_name(),
            'price_name' => $this->price_name(),
            'amount_name' => $this->amount_name(),
        ];
    }
}
