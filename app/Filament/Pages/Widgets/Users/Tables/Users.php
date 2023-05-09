<?php

namespace App\Filament\Pages\Widgets\Users\Tables;

use App\Models\User;
use Closure;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class Users extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder|Relation
    {
        return User::query();
    }

    protected function getTableHeading(): string|Htmlable|Closure|null
    {
        return null;
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\ViewColumn::make('name')
                ->view('filament.components.users.avatar-column')
                ->label('Name')
                ->sortable()
                ->searchable()
                ->grow(false),
            Tables\Columns\TextColumn::make('owned_companies_count')
                ->counts('ownedCompanies')
                ->label('Companies')
                ->weight('semibold')
                ->sortable(),
        ];
    }
}