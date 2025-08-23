<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\RelationManagers;

use App\Filament\Company\Resources\Sales\EstimateResource;
use App\Filament\Company\Resources\Sales\EstimateResource\Pages\CreateEstimate;
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
