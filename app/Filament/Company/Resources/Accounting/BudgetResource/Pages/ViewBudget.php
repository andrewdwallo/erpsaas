<?php

namespace App\Filament\Company\Resources\Accounting\BudgetResource\Pages;

use App\Filament\Company\Resources\Accounting\BudgetResource;
use App\Filament\Company\Resources\Accounting\BudgetResource\RelationManagers\BudgetItemsRelationManager;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ViewBudget extends ViewRecord
{
    protected static string $resource = BudgetResource::class;

    public function getMaxContentWidth(): Width | string | null
    {
        return '8xl';
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getAllRelationManagers(): array
    {
        return [
            BudgetItemsRelationManager::class,
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([]);
    }
}
