<?php

namespace App\Filament\Pages\Widgets;

use App\Models\Company;
use Closure;
use Exception;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class Companies extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder|Relation
    {
        return Company::query();
    }

    protected function getTableHeading(): string|Htmlable|Closure|null
    {
        return null;
    }

    /**
     * @throws Exception
     */
    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('name')
                ->label('Owner')
                ->relationship('owner', 'name'),
            Tables\Filters\TernaryFilter::make('personal_company')
                ->label('Personal Company')
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\ViewColumn::make('owner.name')
                ->view('filament.components.companies.avatar-column')
                ->label('Owner')
                ->sortable()
                ->grow(false),
            Tables\Columns\TextColumn::make('name')
                ->weight('semibold')
                ->label('Company')
                ->sortable(),
            Tables\Columns\TextColumn::make('users_count')
                ->label('Employees')
                ->weight('semibold')
                ->counts('users')
                ->sortable(),
            Tables\Columns\IconColumn::make('personal_company')
                ->label('Personal Company')
                ->boolean()
                ->sortable()
                ->trueIcon('heroicon-o-badge-check')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('primary')
                ->falseColor('secondary')
        ];
    }
}