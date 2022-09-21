<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Pages\Actions;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\ImportField;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getActions(): array
    {
        return [
            ImportAction::make()
            ->fields([
                ImportField::make('transaction_date')
                ->label('Transaction Date')
                ->helperText('The Date of the Transaction'),
                ImportField::make('description')
                ->label('Transaction Description')
                ->helperText('The Description Given by Your Bank'),
                ImportField::make('amount')
                ->label('Transaction Amount')
                ->helperText('The Amount of The Transaction'),
                ImportField::make('running_balance')
                ->label('Running Balance')
                ->helperText('The Running Balance Amount of Your Account During The Transaction'),
            ])
        ];
    }
}
