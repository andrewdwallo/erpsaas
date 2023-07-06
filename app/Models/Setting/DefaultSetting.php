<?php

namespace App\Models\Setting;

use App\Models\Banking\Account;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Wallo\FilamentCompanies\FilamentCompanies;

class DefaultSetting extends Model
{
    use HasFactory;

    protected $table = 'default_settings';

    protected $fillable = [
        'company_id',
        'account_id',
        'currency_code',
        'sales_tax_id',
        'purchase_tax_id',
        'income_category_id',
        'expense_category_id',
        'updated_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    public function salesTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class,'sales_tax_id', 'id');
    }

    public function purchaseTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class,'purchase_tax_id', 'id');
    }

    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class,'income_category_id', 'id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class,'expense_category_id', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public static function getAccounts(): array
    {
        return Account::where('company_id', Auth::user()->currentCompany->id)
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getCurrencies(): array
    {
        return Currency::where('company_id', Auth::user()->currentCompany->id)
            ->pluck('name', 'code')
            ->toArray();
    }

    public static function getSalesTaxes(): array
    {
        return Tax::where('company_id', Auth::user()->currentCompany->id)
            ->where('type', 'sales')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getPurchaseTaxes(): array
    {
        return Tax::where('company_id', Auth::user()->currentCompany->id)
            ->where('type', 'purchase')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getIncomeCategories(): array
    {
        return Category::where('company_id', Auth::user()->currentCompany->id)
            ->where('type', 'income')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getExpenseCategories(): array
    {
        return Category::where('company_id', Auth::user()->currentCompany->id)
            ->where('type', 'expense')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getDefaultAccount()
    {
        $defaultAccount = Account::where('enabled', true)
            ->where('company_id', Auth::user()->currentCompany->id)
            ->first();

        return $defaultAccount->id ?? null;
    }

    public static function getDefaultCurrency()
    {
        $defaultCurrency = Currency::where('enabled', true)
            ->where('company_id', Auth::user()->currentCompany->id)
            ->first();

        return $defaultCurrency->code ?? null;
    }

    public static function getDefaultSalesTax()
    {
        $defaultSalesTax = Tax::where('enabled', true)
            ->where('company_id', Auth::user()->currentCompany->id)
            ->where('type', 'sales')
            ->first();

        return $defaultSalesTax->id ?? null;
    }

    public static function getDefaultPurchaseTax()
    {
        $defaultPurchaseTax = Tax::where('enabled', true)
            ->where('company_id', Auth::user()->currentCompany->id)
            ->where('type', 'purchase')
            ->first();

        return $defaultPurchaseTax->id ?? null;
    }

    public static function getDefaultIncomeCategory()
    {
        $defaultIncomeCategory = Category::where('enabled', true)
            ->where('company_id', Auth::user()->currentCompany->id)
            ->where('type', 'income')
            ->first();

        return $defaultIncomeCategory->id ?? null;
    }

    public static function getDefaultExpenseCategory()
    {
        $defaultExpenseCategory = Category::where('enabled', true)
            ->where('company_id', Auth::user()->currentCompany->id)
            ->where('type', 'expense')
            ->first();

        return $defaultExpenseCategory->id ?? null;
    }
}
