<?php

namespace App\Filament\Company\Resources\Sales\Clients\RelationManagers;

use App\Filament\Company\Resources\Sales\Estimates\EstimateResource;
use App\Filament\Company\Resources\Sales\Estimates\Pages\CreateEstimate;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class EstimatesRelationManager extends RelationManager
{
    protected static string $relationship = 'estimates';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return EstimateResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->url(CreateEstimate::getUrl(['client' => $this->getOwnerRecord()->getKey()])),
            ]);
    }
}
