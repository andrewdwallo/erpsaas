<?php

namespace App\Filament\Tables\Filters;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DateRangeFilter extends Filter
{
    protected string $fromLabel = 'From';

    protected string $untilLabel = 'Until';

    protected ?string $indicatorLabel = null;

    protected ?string $defaultFromDate = null;

    protected ?string $defaultUntilDate = null;

    protected ?string $fromColumn = null;

    protected ?string $untilColumn = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form([
            DatePicker::make('from')
                ->label(fn () => $this->fromLabel)
                ->live()
                ->default(fn () => $this->defaultFromDate)
                ->maxDate(function (Get $get) {
                    return $get('until');
                })
                ->afterStateUpdated(function (Set $set, $state) {
                    if (! $state) {
                        $set('until', null);
                    }
                }),
            DatePicker::make('until')
                ->label(fn () => $this->untilLabel)
                ->live()
                ->default(fn () => $this->defaultUntilDate)
                ->minDate(function (Get $get) {
                    return $get('from');
                }),
        ]);

        $this->query(function (Builder $query, array $data): Builder {
            $fromColumn = $this->fromColumn ?? $this->getName();
            $untilColumn = $this->untilColumn ?? $this->getName();

            $fromDate = filled($data['from'] ?? null)
                ? Carbon::parse($data['from'])
                : null;

            $untilDate = filled($data['until'] ?? null)
                ? Carbon::parse($data['until'])
                : null;

            if (! $fromDate && ! $untilDate) {
                return $query;
            }

            return $this->applyDateFilter($query, $fromDate, $untilDate, $fromColumn, $untilColumn);
        });

        $this->indicateUsing(function (array $data): array {
            $indicators = [];

            $fromDateFormatted = filled($data['from'] ?? null)
                ? Carbon::parse($data['from'])->toDefaultDateFormat()
                : null;

            $untilDateFormatted = filled($data['until'] ?? null)
                ? Carbon::parse($data['until'])->toDefaultDateFormat()
                : null;

            if ($fromDateFormatted && $untilDateFormatted) {
                $indicators[] = Indicator::make($this->getIndicatorLabel() . ': ' . $fromDateFormatted . ' - ' . $untilDateFormatted);
            } else {
                if ($fromDateFormatted) {
                    $indicators[] = Indicator::make($this->fromLabel . ': ' . $fromDateFormatted)
                        ->removeField('from');
                }

                if ($untilDateFormatted) {
                    $indicators[] = Indicator::make($this->untilLabel . ': ' . $untilDateFormatted)
                        ->removeField('until');
                }
            }

            return $indicators;
        });
    }

    protected function applyDateFilter(Builder $query, ?Carbon $fromDate, ?Carbon $untilDate, string $fromColumn, string $untilColumn): Builder
    {
        return $query
            ->when($fromDate && ! $untilDate, function (Builder $query) use ($fromColumn, $fromDate) {
                return $query->where($fromColumn, '>=', $fromDate);
            })
            ->when($fromDate && $untilDate, function (Builder $query) use ($fromColumn, $fromDate, $untilColumn, $untilDate) {
                return $query->where($fromColumn, '>=', $fromDate)
                    ->where($untilColumn, '<=', $untilDate);
            })
            ->when(! $fromDate && $untilDate, function (Builder $query) use ($untilColumn, $untilDate) {
                return $query->where($untilColumn, '<=', $untilDate);
            });
    }

    public function fromLabel(string $label): static
    {
        $this->fromLabel = $label;

        return $this;
    }

    public function untilLabel(string $label): static
    {
        $this->untilLabel = $label;

        return $this;
    }

    public function indicatorLabel(string $label): static
    {
        $this->indicatorLabel = $label;

        return $this;
    }

    public function getIndicatorLabel(): string
    {
        return $this->indicatorLabel ?? Str::headline($this->getName());
    }

    public function defaultFromDate(string $date): static
    {
        $this->defaultFromDate = $date;

        return $this;
    }

    public function defaultUntilDate(string $date): static
    {
        $this->defaultUntilDate = $date;

        return $this;
    }

    public function fromColumn(string $column): static
    {
        $this->fromColumn = $column;

        return $this;
    }

    public function untilColumn(string $column): static
    {
        $this->untilColumn = $column;

        return $this;
    }
}
