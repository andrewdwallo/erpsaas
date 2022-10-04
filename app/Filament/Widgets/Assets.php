<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use Closure;
use Filament\Tables;
use Illuminate\Contracts\Pagination\Paginator;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class Assets extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Asset::query();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('company.name', 'name')->hidden(),
            Tables\Columns\TextColumn::make('department.name', 'name')->hidden(),
            Tables\Columns\TextColumn::make('code'),
            Tables\Columns\TextColumn::make('name'),
            Tables\Columns\TextColumn::make('type'),
            Tables\Columns\TextColumn::make('description')->hidden(),
        ];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

}
