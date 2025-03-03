<?php

namespace App\Filament\Company\Resources\Purchases\VendorResource\RelationManagers;

use App\Filament\Company\Resources\Purchases\BillResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BillsRelationManager extends RelationManager
{
    protected static string $relationship = 'bills';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return BillResource::table($table)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->url(BillResource\Pages\CreateBill::getUrl(['vendor' => $this->getOwnerRecord()->getKey()])),
            ]);
    }
}
