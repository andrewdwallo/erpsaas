<?php

namespace App\Models\Accounting;

use App\Casts\RateCast;
use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\AdjustmentScope;
use App\Enums\Accounting\AdjustmentType;
use App\Models\Common\Offering;
use App\Observers\AdjustmentObserver;
use Database\Factories\Accounting\AdjustmentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[ObservedBy(AdjustmentObserver::class)]
class Adjustment extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'adjustments';

    protected $fillable = [
        'company_id',
        'account_id',
        'name',
        'description',
        'category',
        'type',
        'recoverable',
        'rate',
        'computation',
        'scope',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'category' => AdjustmentCategory::class,
        'type' => AdjustmentType::class,
        'recoverable' => 'boolean',
        'rate' => RateCast::class,
        'computation' => AdjustmentComputation::class,
        'scope' => AdjustmentScope::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function offerings(): MorphToMany
    {
        return $this->morphedByMany(Offering::class, 'adjustmentable', 'adjustmentables');
    }

    public function isSalesTax(): bool
    {
        return $this->category->isTax() && $this->type->isSales();
    }

    public function isNonRecoverablePurchaseTax(): bool
    {
        return $this->category->isTax() && $this->type->isPurchase() && $this->recoverable === false;
    }

    public function isRecoverablePurchaseTax(): bool
    {
        return $this->category->isTax() && $this->type->isPurchase() && $this->recoverable === true;
    }

    public function isSalesDiscount(): bool
    {
        return $this->category->isDiscount() && $this->type->isSales();
    }

    public function isPurchaseDiscount(): bool
    {
        return $this->category->isDiscount() && $this->type->isPurchase();
    }

    protected static function newFactory(): Factory
    {
        return AdjustmentFactory::new();
    }
}
