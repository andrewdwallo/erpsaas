<?php

namespace App\Models\Setting;

use App\Enums\CategoryType;
use App\Enums\DiscountType;
use App\Enums\TaxType;
use App\Models\Banking\BankAccount;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Setting\CompanyDefaultFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Wallo\FilamentCompanies\FilamentCompanies;

class CompanyDefault extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'company_defaults';

    protected $fillable = [
        'company_id',
        'bank_account_id',
        'currency_code',
        'sales_tax_id',
        'purchase_tax_id',
        'sales_discount_id',
        'purchase_discount_id',
        'income_category_id',
        'expense_category_id',
        'created_by',
        'updated_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function salesTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'sales_tax_id', 'id')
            ->where('type', TaxType::Sales);
    }

    public function purchaseTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'purchase_tax_id', 'id')
            ->where('type', TaxType::Purchase);
    }

    public function salesDiscount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'sales_discount_id', 'id')
            ->where('type', DiscountType::Sales);
    }

    public function purchaseDiscount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'purchase_discount_id', 'id')
            ->where('type', DiscountType::Purchase);
    }

    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'income_category_id', 'id')
            ->where('type', CategoryType::Income);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'expense_category_id', 'id')
            ->where('type', CategoryType::Expense);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    protected static function newFactory(): Factory
    {
        return CompanyDefaultFactory::new();
    }
}
