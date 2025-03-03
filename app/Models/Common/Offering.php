<?php

namespace App\Models\Common;

use App\Casts\MoneyCast;
use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentType;
use App\Enums\Common\OfferingType;
use App\Models\Accounting\Account;
use App\Models\Accounting\Adjustment;
use App\Observers\OfferingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[ObservedBy(OfferingObserver::class)]
class Offering extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'type',
        'price',
        'sellable',
        'purchasable',
        'income_account_id',
        'expense_account_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => OfferingType::class,
        'price' => MoneyCast::class,
        'sellable' => 'boolean',
        'purchasable' => 'boolean',
    ];

    public function clearSellableAdjustments(): void
    {
        if (! $this->sellable) {
            $this->income_account_id = null;

            $adjustmentIds = $this->salesAdjustments()->pluck('adjustment_id');

            $this->adjustments()->detach($adjustmentIds);
        }
    }

    public function clearPurchasableAdjustments(): void
    {
        if (! $this->purchasable) {
            $this->expense_account_id = null;

            $adjustmentIds = $this->purchaseAdjustments()->pluck('adjustment_id');

            $this->adjustments()->detach($adjustmentIds);
        }
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function adjustments(): MorphToMany
    {
        return $this->morphToMany(Adjustment::class, 'adjustmentable', 'adjustmentables');
    }

    public function salesAdjustments(): MorphToMany
    {
        return $this->adjustments()->where('type', AdjustmentType::Sales);
    }

    public function purchaseAdjustments(): MorphToMany
    {
        return $this->adjustments()->where('type', AdjustmentType::Purchase);
    }

    public function salesTaxes(): MorphToMany
    {
        return $this->adjustments()->where('category', AdjustmentCategory::Tax)->where('type', AdjustmentType::Sales);
    }

    public function purchaseTaxes(): MorphToMany
    {
        return $this->adjustments()->where('category', AdjustmentCategory::Tax)->where('type', AdjustmentType::Purchase);
    }

    public function salesDiscounts(): MorphToMany
    {
        return $this->adjustments()->where('category', AdjustmentCategory::Discount)->where('type', AdjustmentType::Sales);
    }

    public function purchaseDiscounts(): MorphToMany
    {
        return $this->adjustments()->where('category', AdjustmentCategory::Discount)->where('type', AdjustmentType::Purchase);
    }
}
