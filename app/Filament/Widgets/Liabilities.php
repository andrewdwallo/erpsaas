<?php

namespace App\Filament\Widgets;

use App\Models\Liability;
use Closure;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class Liabilities extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder
    {
        return Liability::query();
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
