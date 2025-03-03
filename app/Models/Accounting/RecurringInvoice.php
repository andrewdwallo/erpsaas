<?php

namespace App\Models\Accounting;

use App\Casts\MoneyCast;
use App\Casts\RateCast;
use App\Collections\Accounting\DocumentCollection;
use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\DayOfMonth;
use App\Enums\Accounting\DayOfWeek;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\DocumentType;
use App\Enums\Accounting\EndType;
use App\Enums\Accounting\Frequency;
use App\Enums\Accounting\IntervalType;
use App\Enums\Accounting\InvoiceStatus;
use App\Enums\Accounting\Month;
use App\Enums\Accounting\RecurringInvoiceStatus;
use App\Enums\Setting\PaymentTerms;
use App\Filament\Forms\Components\Banner;
use App\Filament\Forms\Components\CustomSection;
use App\Models\Common\Client;
use App\Models\Setting\CompanyProfile;
use App\Observers\RecurringInvoiceObserver;
use App\Support\ScheduleHandler;
use App\Utilities\Localization\Timezone;
use Filament\Actions\Action;
use Filament\Actions\MountableAction;
use Filament\Forms;
use Filament\Forms\Form;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[CollectedBy(DocumentCollection::class)]
#[ObservedBy(RecurringInvoiceObserver::class)]
class RecurringInvoice extends Document
{
    protected $table = 'recurring_invoices';

    protected $fillable = [
        'company_id',
        'client_id',
        'logo',
        'header',
        'subheader',
        'order_number',
        'payment_terms',
        'approved_at',
        'ended_at',
        'frequency',
        'interval_type',
        'interval_value',
        'month',
        'day_of_month',
        'day_of_week',
        'start_date',
        'end_type',
        'max_occurrences',
        'end_date',
        'occurrences_count',
        'timezone',
        'next_date',
        'last_date',
        'auto_send',
        'send_time',
        'status',
        'currency_code',
        'discount_method',
        'discount_computation',
        'discount_rate',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'terms',
        'footer',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'ended_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_date' => 'date',
        'last_date' => 'date',
        'auto_send' => 'boolean',
        'send_time' => 'datetime:H:i',
        'payment_terms' => PaymentTerms::class,
        'frequency' => Frequency::class,
        'interval_type' => IntervalType::class,
        'interval_value' => 'integer',
        'month' => Month::class,
        'day_of_month' => DayOfMonth::class,
        'day_of_week' => DayOfWeek::class,
        'end_type' => EndType::class,
        'status' => RecurringInvoiceStatus::class,
        'discount_method' => DocumentDiscountMethod::class,
        'discount_computation' => AdjustmentComputation::class,
        'discount_rate' => RateCast::class,
        'subtotal' => MoneyCast::class,
        'tax_total' => MoneyCast::class,
        'discount_total' => MoneyCast::class,
        'total' => MoneyCast::class,
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'recurring_invoice_id');
    }

    public static function documentType(): DocumentType
    {
        return DocumentType::RecurringInvoice;
    }

    public function documentNumber(): ?string
    {
        return 'Auto-generated';
    }

    public function documentDate(): ?string
    {
        return $this->calculateNextDate()?->toDefaultDateFormat() ?? 'Auto-generated';
    }

    public function dueDate(): ?string
    {
        return $this->calculateNextDueDate()?->toDefaultDateFormat() ?? 'Auto-generated';
    }

    public function referenceNumber(): ?string
    {
        return $this->order_number;
    }

    public function amountDue(): ?string
    {
        return $this->total;
    }

    public function isDraft(): bool
    {
        return $this->status === RecurringInvoiceStatus::Draft;
    }

    public function isActive(): bool
    {
        return $this->status === RecurringInvoiceStatus::Active;
    }

    public function wasApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function wasEnded(): bool
    {
        return $this->ended_at !== null;
    }

    public function isNeverEnding(): bool
    {
        return $this->end_type === EndType::Never;
    }

    public function canBeApproved(): bool
    {
        return $this->isDraft() && $this->hasSchedule() && ! $this->wasApproved() && $this->hasValidStartDate();
    }

    public function canBeEnded(): bool
    {
        return $this->isActive() && ! $this->wasEnded();
    }

    public function hasSchedule(): bool
    {
        return $this->start_date !== null;
    }

    public function hasValidStartDate(): bool
    {
        if ($this->wasApproved()) {
            return true;
        }

        // For unapproved/draft invoices, start date must be today or in the future
        return $this->start_date?->gte(today()) ?? false;
    }

    public function getScheduleDescription(): ?string
    {
        if (! $this->hasSchedule()) {
            return null;
        }

        $frequency = $this->frequency;

        return match (true) {
            $frequency->isDaily() => 'Repeat daily',

            $frequency->isWeekly() && $this->day_of_week => "Repeat weekly every {$this->day_of_week->getLabel()}",

            $frequency->isMonthly() && $this->day_of_month => "Repeat monthly on the {$this->day_of_month->getLabel()} day",

            $frequency->isYearly() && $this->month && $this->day_of_month => "Repeat yearly on {$this->month->getLabel()} {$this->day_of_month->getLabel()}",

            $frequency->isCustom() => $this->getCustomScheduleDescription(),

            default => null,
        };
    }

    private function getCustomScheduleDescription(): string
    {
        $interval = $this->interval_value > 1
            ? "{$this->interval_value} {$this->interval_type->getPluralLabel()}"
            : $this->interval_type->getSingularLabel();

        $dayDescription = match (true) {
            $this->interval_type->isWeek() && $this->day_of_week => " on {$this->day_of_week->getLabel()}",

            $this->interval_type->isMonth() && $this->day_of_month => " on the {$this->day_of_month->getLabel()} day",

            $this->interval_type->isYear() && $this->month && $this->day_of_month => " on {$this->month->getLabel()} {$this->day_of_month->getLabel()}",

            default => ''
        };

        return "Repeat every {$interval}{$dayDescription}";
    }

    public function getEndDescription(): ?string
    {
        if (! $this->end_type) {
            return null;
        }

        return match (true) {
            $this->end_type->isNever() => 'Never',

            $this->end_type->isAfter() && $this->max_occurrences => "After {$this->max_occurrences} " . str($this->max_occurrences === 1 ? 'invoice' : 'invoices'),

            $this->end_type->isOn() && $this->end_date => 'On ' . $this->end_date->toDefaultDateFormat(),

            default => null,
        };
    }

    public function getTimelineDescription(): ?string
    {
        if (! $this->hasSchedule()) {
            return null;
        }

        $parts = [];

        if ($this->start_date) {
            $parts[] = 'First Invoice: ' . $this->start_date->toDefaultDateFormat();
        }

        if ($this->end_type) {
            $parts[] = 'Ends: ' . $this->getEndDescription();
        }

        return implode(', ', $parts);
    }

    public function calculateNextDate(?Carbon $lastDate = null): ?Carbon
    {
        $lastDate ??= $this->last_date;

        if (! $lastDate && $this->start_date && $this->wasApproved()) {
            return $this->start_date;
        }

        if (! $lastDate) {
            return null;
        }

        $nextDate = match (true) {
            $this->frequency->isDaily() => $lastDate->copy()->addDay(),

            $this->frequency->isWeekly() => $this->calculateNextWeeklyDate($lastDate),

            $this->frequency->isMonthly() => $this->calculateNextMonthlyDate($lastDate),

            $this->frequency->isYearly() => $this->calculateNextYearlyDate($lastDate),

            $this->frequency->isCustom() => $this->calculateCustomNextDate($lastDate),

            default => null
        };

        if (! $nextDate || $this->hasReachedEnd($nextDate)) {
            return null;
        }

        return $nextDate;
    }

    public function calculateNextWeeklyDate(Carbon $lastDate, int $interval = 1): ?Carbon
    {
        return $lastDate->copy()
            ->addWeeks($interval - 1)
            ->next($this->day_of_week->value);
    }

    public function calculateNextMonthlyDate(Carbon $lastDate, int $interval = 1): ?Carbon
    {
        return $this->day_of_month->resolveDate(
            $lastDate->copy()->addMonthsNoOverflow($interval)
        );
    }

    public function calculateNextYearlyDate(Carbon $lastDate, int $interval = 1): ?Carbon
    {
        return $this->day_of_month->resolveDate(
            $lastDate->copy()->addYears($interval)->month($this->month->value)
        );
    }

    protected function calculateCustomNextDate(Carbon $lastDate): ?Carbon
    {
        $interval = $this->interval_value ?? 1;

        return match ($this->interval_type) {
            IntervalType::Day => $lastDate->copy()->addDays($interval),

            IntervalType::Week => $this->calculateNextWeeklyDate($lastDate, $interval),

            IntervalType::Month => $this->calculateNextMonthlyDate($lastDate, $interval),

            IntervalType::Year => $this->calculateNextYearlyDate($lastDate, $interval),

            default => null
        };
    }

    public function calculateNextDueDate(): ?Carbon
    {
        if (! $nextDate = $this->calculateNextDate()) {
            return null;
        }

        if (! $terms = $this->payment_terms) {
            return $nextDate;
        }

        return $nextDate->copy()->addDays($terms->getDays());
    }

    public function hasReachedEnd(?Carbon $nextDate = null): bool
    {
        if (! $this->end_type) {
            return false;
        }

        return match (true) {
            $this->end_type->isNever() => false,

            $this->end_type->isAfter() => ($this->occurrences_count ?? 0) >= ($this->max_occurrences ?? 0),

            $this->end_type->isOn() && $this->end_date && $nextDate => $nextDate->greaterThan($this->end_date),

            default => false
        };
    }

    public static function getManageScheduleAction(string $action = Action::class): MountableAction
    {
        return $action::make('manageSchedule')
            ->label(fn (self $record) => $record->hasSchedule() ? 'Edit schedule' : 'Set schedule')
            ->icon('heroicon-m-calendar-date-range')
            ->slideOver()
            ->successNotificationTitle('Schedule saved')
            ->mountUsing(function (self $record, Form $form) {
                $data = $record->attributesToArray();

                $data['day_of_month'] ??= DayOfMonth::First;
                $data['start_date'] ??= now()->addMonth()->startOfMonth();

                $form->fill($data);
            })
            ->form([
                CustomSection::make('Frequency')
                    ->contained(false)
                    ->schema(function (Forms\Get $get) {
                        $frequency = Frequency::parse($get('frequency'));
                        $intervalType = IntervalType::parse($get('interval_type'));
                        $month = Month::parse($get('month'));
                        $dayOfMonth = DayOfMonth::parse($get('day_of_month'));

                        return [
                            Forms\Components\Select::make('frequency')
                                ->label('Repeats')
                                ->options(Frequency::class)
                                ->softRequired()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    $handler = new ScheduleHandler($set);
                                    $handler->handleFrequencyChange($state);
                                }),

                            Cluster::make([
                                Forms\Components\TextInput::make('interval_value')
                                    ->softRequired()
                                    ->numeric()
                                    ->default(1),
                                Forms\Components\Select::make('interval_type')
                                    ->options(IntervalType::class)
                                    ->softRequired()
                                    ->default(IntervalType::Month)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        $handler = new ScheduleHandler($set);
                                        $handler->handleIntervalTypeChange($state);
                                    }),
                            ])
                                ->live()
                                ->label('Every')
                                ->required()
                                ->markAsRequired(false)
                                ->visible($frequency->isCustom()),

                            Forms\Components\Select::make('month')
                                ->label('Month')
                                ->options(Month::class)
                                ->softRequired()
                                ->visible($frequency->isYearly() || $intervalType?->isYear())
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $handler = new ScheduleHandler($set, $get);
                                    $handler->handleDateChange('month', $state);
                                }),

                            Forms\Components\Select::make('day_of_month')
                                ->label('Day of Month')
                                ->options(function () use ($month) {
                                    if (! $month) {
                                        return DayOfMonth::class;
                                    }

                                    $daysInMonth = Carbon::createFromDate(null, $month->value)->daysInMonth;

                                    return collect(DayOfMonth::cases())
                                        ->filter(static fn (DayOfMonth $dayOfMonth) => $dayOfMonth->value <= $daysInMonth || $dayOfMonth->isLast())
                                        ->mapWithKeys(fn (DayOfMonth $dayOfMonth) => [$dayOfMonth->value => $dayOfMonth->getLabel()]);
                                })
                                ->softRequired()
                                ->visible(in_array($frequency, [Frequency::Monthly, Frequency::Yearly]) || in_array($intervalType, [IntervalType::Month, IntervalType::Year]))
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    $handler = new ScheduleHandler($set, $get);
                                    $handler->handleDateChange('day_of_month', $state);
                                }),

                            Banner::make('dayOfMonthNotice')
                                ->info()
                                ->title(static function () use ($dayOfMonth) {
                                    return "For months with fewer than {$dayOfMonth->value} days, the last day of the month will be used.";
                                })
                                ->columnSpanFull()
                                ->visible($dayOfMonth?->mayExceedMonthLength() && ($frequency->isMonthly() || $intervalType?->isMonth())),

                            Forms\Components\Select::make('day_of_week')
                                ->label('Day of Week')
                                ->options(DayOfWeek::class)
                                ->softRequired()
                                ->visible(($frequency->isWeekly() || $intervalType?->isWeek()) ?? false)
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    $handler = new ScheduleHandler($set);
                                    $handler->handleDateChange('day_of_week', $state);
                                }),
                        ];
                    })->columns(2),

                CustomSection::make('Dates & Time')
                    ->contained(false)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('First invoice date')
                            ->softRequired()
                            ->live()
                            ->minDate(today())
                            ->closeOnDateSelection()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                $handler = new ScheduleHandler($set, $get);
                                $handler->handleDateChange('start_date', $state);
                            }),

                        Forms\Components\Group::make(function (Forms\Get $get) {
                            $components = [];

                            $components[] = Forms\Components\Select::make('end_type')
                                ->label('End schedule')
                                ->options(EndType::class)
                                ->softRequired()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    $endType = EndType::parse($state);

                                    $set('max_occurrences', $endType?->isAfter() ? 1 : null);
                                    $set('end_date', $endType?->isOn() ? now()->addMonth()->startOfMonth() : null);
                                });

                            $endType = EndType::parse($get('end_type'));

                            if ($endType?->isAfter()) {
                                $components[] = Forms\Components\TextInput::make('max_occurrences')
                                    ->numeric()
                                    ->suffix('invoices')
                                    ->live();
                            }

                            if ($endType?->isOn()) {
                                $components[] = Forms\Components\DatePicker::make('end_date')
                                    ->live();
                            }

                            return [
                                Cluster::make($components)
                                    ->label('Schedule ends')
                                    ->required()
                                    ->markAsRequired(false),
                            ];
                        }),

                        Forms\Components\Select::make('timezone')
                            ->options(Timezone::getTimezoneOptions(CompanyProfile::first()->country))
                            ->searchable()
                            ->softRequired(),
                    ])
                    ->columns(2),
            ])
            ->action(function (self $record, array $data, MountableAction $action) {
                $record->update($data);

                $action->success();
            });
    }

    public static function getApproveDraftAction(string $action = Action::class): MountableAction
    {
        return $action::make('approveDraft')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->visible(function (self $record) {
                return $record->canBeApproved();
            })
            ->databaseTransaction()
            ->successNotificationTitle('Recurring invoice approved')
            ->action(function (self $record, MountableAction $action) {
                $record->approveDraft();

                $action->success();
            });
    }

    public function approveDraft(?Carbon $approvedAt = null): void
    {
        if (! $this->isDraft()) {
            throw new \RuntimeException('Invoice is not in draft status.');
        }

        $approvedAt ??= now();

        $this->update([
            'approved_at' => $approvedAt,
            'status' => RecurringInvoiceStatus::Active,
        ]);
    }

    public function generateInvoice(): ?Invoice
    {
        if (! $this->shouldGenerateInvoice()) {
            return null;
        }

        $nextDate = $this->next_date ?? $this->calculateNextDate();

        if (! $nextDate) {
            return null;
        }

        $dueDate = $this->calculateNextDueDate();

        $invoice = $this->invoices()->create([
            'company_id' => $this->company_id,
            'client_id' => $this->client_id,
            'logo' => $this->logo,
            'header' => $this->header,
            'subheader' => $this->subheader,
            'invoice_number' => Invoice::getNextDocumentNumber($this->company),
            'date' => $nextDate,
            'due_date' => $dueDate,
            'status' => InvoiceStatus::Draft,
            'currency_code' => $this->currency_code,
            'discount_method' => $this->discount_method,
            'discount_computation' => $this->discount_computation,
            'discount_rate' => $this->discount_rate,
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'discount_total' => $this->discount_total,
            'total' => $this->total,
            'terms' => $this->terms,
            'footer' => $this->footer,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->replicateLineItems($invoice);

        $this->update([
            'last_date' => $nextDate,
            'next_date' => $this->calculateNextDate($nextDate),
            'occurrences_count' => ($this->occurrences_count ?? 0) + 1,
        ]);

        return $invoice;
    }

    public function replicateLineItems(Model $target): void
    {
        $this->lineItems->each(function (DocumentLineItem $lineItem) use ($target) {
            $replica = $lineItem->replicate([
                'documentable_id',
                'documentable_type',
                'subtotal',
                'total',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at',
            ]);

            $replica->documentable_id = $target->id;
            $replica->documentable_type = $target->getMorphClass();
            $replica->save();

            $replica->adjustments()->sync($lineItem->adjustments->pluck('id'));
        });
    }

    public function shouldGenerateInvoice(): bool
    {
        if (! $this->isActive() || $this->hasReachedEnd()) {
            return false;
        }

        $nextDate = $this->calculateNextDate();

        if (! $nextDate || $nextDate->startOfDay()->isFuture()) {
            return false;
        }

        return true;
    }

    public function generateDueInvoices(): void
    {
        $maxIterations = 100;

        for ($i = 0; $i < $maxIterations; $i++) {
            $result = $this->generateInvoice();

            if (! $result) {
                break;
            }

            $this->refresh();
        }
    }
}
