<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\RelationManagers;

use App\Filament\Company\Resources\Sales\EstimateResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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
                Tables\Actions\CreateAction::make()
                    ->url(EstimateResource\Pages\CreateEstimate::getUrl(['client' => $this->getOwnerRecord()->getKey()])),
            ]);
    }
}
