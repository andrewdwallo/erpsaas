<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\RelationManagers;

use App\Filament\Company\Resources\Sales\RecurringInvoiceResource;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages\CreateRecurringInvoice;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class RecurringInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'recurringInvoices';

    protected static bool $isLazy = false;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return RecurringInvoiceResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->url(CreateRecurringInvoice::getUrl(['client' => $this->getOwnerRecord()->getKey()])),
            ]);
    }
}
