<?php

namespace App\Filament\Pages\Widgets;

use App\Models\User;
use Closure;
use Exception;
use Filament\Tables;
use Filament\Widgets\TableWidget as PageWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class Employees extends PageWidget
{
    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getTableQuery(): Builder|Relation
    {
        return User::whereHas('employeeships');
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
                ->label('Company')
                ->relationship('companies', 'name', static fn (Builder $query) => $query->whereHas('users')),
        ];
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
            Tables\Columns\TextColumn::make('companies.name')
                ->label('Company')
                ->sortable()
                ->searchable()
                ->weight('semibold'),
            Tables\Columns\BadgeColumn::make('employeeships.role')
                ->label('Role')
                ->enum([
                    'admin' => 'Administrator',
                    'editor' => 'Editor',
                ])
                ->icons([
                    'heroicon-o-shield-check' => 'admin',
                    'heroicon-o-pencil' => 'editor',
                ])
                ->colors([
                    'primary' => 'admin',
                    'warning' => 'editor',
                ])
                ->sortable(),
        ];
    }
}