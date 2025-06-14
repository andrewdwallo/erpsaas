<?php

namespace App\Models\Accounting;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Accounting\BudgetIntervalType;
use App\Enums\Accounting\BudgetSourceType;
use App\Enums\Accounting\BudgetStatus;
use App\Filament\Company\Resources\Accounting\BudgetResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ReplicateAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class Budget extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $fillable = [
        'company_id',
        'source_budget_id',
        'source_fiscal_year',
        'source_type',
        'name',
        'start_date',
        'end_date',
        'status', // draft, active, closed
        'interval_type', // day, week, month, quarter, year
        'notes',
        'approved_at',
        'approved_by_id',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'source_fiscal_year' => 'integer',
        'source_type' => BudgetSourceType::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => BudgetStatus::class,
        'interval_type' => BudgetIntervalType::class,
        'approved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function sourceBudget(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_budget_id');
    }

    public function derivedBudgets(): HasMany
    {
        return $this->hasMany(self::class, 'source_budget_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function allocations(): HasManyThrough
    {
        return $this->hasManyThrough(BudgetAllocation::class, BudgetItem::class);
    }

    public function getPeriods(): Collection
    {
        return $this->allocations()
            ->select(['period', 'start_date'])
            ->distinct()
            ->orderBy('start_date')
            ->get();
    }

    public function isDraft(): bool
    {
        return $this->status === BudgetStatus::Draft;
    }

    public function isActive(): bool
    {
        return $this->status === BudgetStatus::Active;
    }

    public function isClosed(): bool
    {
        return $this->status === BudgetStatus::Closed;
    }

    public function wasApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function wasClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function canBeApproved(): bool
    {
        return $this->isDraft() && ! $this->wasApproved();
    }

    public function canBeClosed(): bool
    {
        return $this->isActive() && ! $this->wasClosed();
    }

    public function hasItems(): bool
    {
        return $this->budgetItems()->exists();
    }

    public function hasAllocations(): bool
    {
        return $this->allocations()->exists();
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', BudgetStatus::Draft);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BudgetStatus::Active);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', BudgetStatus::Closed);
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->active()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    protected function isCurrentlyInPeriod(): Attribute
    {
        return Attribute::get(function () {
            return now()->between($this->start_date, $this->end_date);
        });
    }

    /**
     * Approve a draft budget
     */
    public function approveDraft(?Carbon $approvedAt = null): void
    {
        if (! $this->canBeApproved()) {
            throw new RuntimeException('Budget cannot be approved.');
        }

        $approvedAt ??= now();

        $this->update([
            'status' => BudgetStatus::Active,
            'approved_at' => $approvedAt,
        ]);
    }

    /**
     * Close an active budget
     */
    public function close(?Carbon $closedAt = null): void
    {
        if (! $this->canBeClosed()) {
            throw new RuntimeException('Budget cannot be closed.');
        }

        $closedAt ??= now();

        $this->update([
            'status' => BudgetStatus::Closed,
            'closed_at' => $closedAt,
        ]);
    }

    /**
     * Reopen a closed budget
     */
    public function reopen(): void
    {
        if (! $this->isClosed()) {
            throw new RuntimeException('Only closed budgets can be reopened.');
        }

        $this->update([
            'status' => BudgetStatus::Active,
            'closed_at' => null,
        ]);
    }

    /**
     * Get Action for approving a draft budget
     */
    public static function getApproveDraftAction(string $action = Action::class): Action
    {
        return $action::make('approveDraft')
            ->label('Approve')
            ->icon('heroicon-m-check-circle')
            ->visible(function (self $record) {
                return $record->canBeApproved();
            })
            ->databaseTransaction()
            ->successNotificationTitle('Budget approved')
            ->action(function (self $record, Action $action) {
                $record->approveDraft();
                $action->success();
            });
    }

    /**
     * Get Action for closing an active budget
     */
    public static function getCloseAction(string $action = Action::class): Action
    {
        return $action::make('close')
            ->label('Close')
            ->icon('heroicon-m-lock-closed')
            ->color('warning')
            ->visible(function (self $record) {
                return $record->canBeClosed();
            })
            ->requiresConfirmation()
            ->databaseTransaction()
            ->successNotificationTitle('Budget closed')
            ->action(function (self $record, Action $action) {
                $record->close();
                $action->success();
            });
    }

    /**
     * Get Action for reopening a closed budget
     */
    public static function getReopenAction(string $action = Action::class): Action
    {
        return $action::make('reopen')
            ->label('Reopen')
            ->icon('heroicon-m-lock-open')
            ->visible(function (self $record) {
                return $record->isClosed();
            })
            ->requiresConfirmation()
            ->databaseTransaction()
            ->successNotificationTitle('Budget reopened')
            ->action(function (self $record, Action $action) {
                $record->reopen();
                $action->success();
            });
    }

    /**
     * Get Action for duplicating a budget
     */
    public static function getReplicateAction(string $action = ReplicateAction::class): Action
    {
        return $action::make()
            ->excludeAttributes([
                'status',
                'approved_at',
                'closed_at',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ])
            ->modal(false)
            ->beforeReplicaSaved(function (self $original, self $replica) {
                $replica->status = BudgetStatus::Draft;
                $replica->name = $replica->name . ' (Copy)';
            })
            ->databaseTransaction()
            ->after(function (self $original, self $replica) {
                // Clone budget items and their allocations
                $original->budgetItems->each(function (BudgetItem $item) use ($replica) {
                    $newItem = $item->replicate([
                        'budget_id',
                        'created_by',
                        'updated_by',
                        'created_at',
                        'updated_at',
                    ]);

                    $newItem->budget_id = $replica->id;
                    $newItem->save();

                    // Clone the allocations for this budget item
                    $item->allocations->each(function (BudgetAllocation $allocation) use ($newItem) {
                        $newAllocation = $allocation->replicate([
                            'budget_item_id',
                            'created_at',
                            'updated_at',
                        ]);

                        $newAllocation->budget_item_id = $newItem->id;
                        $newAllocation->save();
                    });
                });
            })
            ->successRedirectUrl(static function (self $replica) {
                return BudgetResource::getUrl('edit', ['record' => $replica]);
            });
    }
}
