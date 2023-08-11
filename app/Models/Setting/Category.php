<?php

namespace App\Models\Setting;

use App\Models\Document\Document;
use App\Models\Item;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Wallo\FilamentCompanies\FilamentCompanies;

class Category extends Model
{
    use Blamable, CompanyOwned, HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'color',
        'enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public static function getCategoryTypes(): array
    {
        return [
            'expense' => 'Expense',
            'income' => 'Income',
            'item' => 'Item',
            'other' => 'Other',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function defaultIncomeCategory(): HasOne
    {
        return $this->hasOne(DefaultSetting::class, 'income_category_id');
    }

    public function defaultExpenseCategory(): HasOne
    {
        return $this->hasOne(DefaultSetting::class, 'expense_category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function bills(): HasMany
    {
        return $this->documents()->where('type', 'bill');
    }

    public function invoices(): HasMany
    {
        return $this->documents()->where('type', 'invoice');
    }

    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }
}
