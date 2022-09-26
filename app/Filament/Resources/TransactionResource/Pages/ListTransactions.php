<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use Filament\Forms\Components\Actions\Modal\Actions\Action;
use Filament\Pages\Actions;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\ImportField;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Konnco\FilamentImport\Actions\ImportField as ActionsImportField;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getActions(): array
    {
        return [
            ImportAction::make()
            ->fields([
                Select::make('company_id')
                ->label('Company')
                ->relationship('company', 'name')->nullable()
                ->helperText('The Name of The Company'),
                ActionsImportField::make('transaction_date')
                ->label('Transaction Date')
                ->helperText('The Date of the Transaction'),
                ActionsImportField::make('description')
                ->label('Transaction Description')
                ->helperText('The Description Given by Your Bank'),
                ActionsImportField::make('amount')
                ->label('Transaction Amount')
                ->helperText('The Amount of The Transaction'),
                ActionsImportField::make('running_balance')
                ->label('Running Balance')
                ->helperText('The Running Balance Amount of Your Account During The Transaction'),
            ])

        ];
    }
}
