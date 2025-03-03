<?php

namespace App\Models\Setting;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\DocumentType;
use App\Enums\Setting\Font;
use App\Enums\Setting\PaymentTerms;
use App\Enums\Setting\Template;
use Database\Factories\Setting\DocumentDefaultFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DocumentDefault extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'document_defaults';

    protected $fillable = [
        'company_id',
        'type',
        'logo',
        'show_logo',
        'number_prefix',
        'payment_terms',
        'header',
        'subheader',
        'terms',
        'footer',
        'accent_color',
        'font',
        'template',
        'item_name',
        'unit_name',
        'price_name',
        'amount_name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => DocumentType::class,
        'show_logo' => 'boolean',
        'payment_terms' => PaymentTerms::class,
        'font' => Font::class,
        'template' => Template::class,
        'item_name' => AsArrayObject::class,
        'unit_name' => AsArrayObject::class,
        'price_name' => AsArrayObject::class,
        'amount_name' => AsArrayObject::class,
    ];

    protected $appends = [
        'logo_url',
    ];

    protected function logoUrl(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): ?string {
            return $attributes['logo'] ? Storage::disk('public')->url($attributes['logo']) : null;
        });
    }

    public function scopeType(Builder $query, string | DocumentType $type): Builder
    {
        return $query->where($this->qualifyColumn('type'), $type);
    }

    public function scopeInvoice(Builder $query): Builder
    {
        return $query->type(DocumentType::Invoice);
    }

    public function scopeRecurringInvoice(Builder $query): Builder
    {
        return $query->type(DocumentType::RecurringInvoice);
    }

    public function scopeBill(Builder $query): Builder
    {
        return $query->type(DocumentType::Bill);
    }

    public function scopeEstimate(Builder $query): Builder
    {
        return $query->type(DocumentType::Estimate);
    }

    public function getNumberNext(?string $prefix = null, int | string | null $next = null): string
    {
        $numberPrefix = $prefix ?? $this->number_prefix ?? '';
        $numberNext = (string) ($next ?? (static::getBaseNumber() + 1));

        return $numberPrefix . $numberNext;
    }

    public static function getBaseNumber(): int
    {
        return 1000;
    }

    public static function getAvailableItemNameOptions(): array
    {
        $options = [
            'items' => 'Items',
            'products' => 'Products',
            'services' => 'Services',
            'other' => 'Other',
        ];

        return array_map(translate(...), $options);
    }

    public static function getAvailableUnitNameOptions(): array
    {
        $options = [
            'quantity' => 'Quantity',
            'hours' => 'Hours',
            'other' => 'Other',
        ];

        return array_map(translate(...), $options);
    }

    public static function getAvailablePriceNameOptions(): array
    {
        $options = [
            'price' => 'Price',
            'rate' => 'Rate',
            'other' => 'Other',
        ];

        return array_map(translate(...), $options);
    }

    public static function getAvailableAmountNameOptions(): array
    {
        $options = [
            'amount' => 'Amount',
            'total' => 'Total',
            'other' => 'Other',
        ];

        return array_map(translate(...), $options);
    }

    public function getLabelOptionFor(string $optionType, ?string $optionValue)
    {
        $optionValue = $optionValue ?? $this->{$optionType}['option'];

        if (! $optionValue) {
            return null;
        }

        $options = match ($optionType) {
            'item_name' => static::getAvailableItemNameOptions(),
            'unit_name' => static::getAvailableUnitNameOptions(),
            'price_name' => static::getAvailablePriceNameOptions(),
            'amount_name' => static::getAvailableAmountNameOptions(),
            default => [],
        };

        return $options[$optionValue] ?? null;
    }

    public function resolveColumnLabel(string $column, string $default, ?array $data = null): string
    {
        if ($data) {
            $custom = $data[$column]['custom'] ?? null;
            $option = $data[$column]['option'] ?? null;
        } else {
            $custom = $this->{$column}['custom'] ?? null;
            $option = $this->{$column}['option'] ?? null;
        }

        if ($custom) {
            return $custom;
        }

        return $this->getLabelOptionFor($column, $option) ?? $default;
    }

    protected static function newFactory(): Factory
    {
        return DocumentDefaultFactory::new();
    }
}
