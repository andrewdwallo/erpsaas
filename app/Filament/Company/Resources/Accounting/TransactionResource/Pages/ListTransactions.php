<?php

namespace App\Filament\Company\Resources\Accounting\TransactionResource\Pages;

use App\Concerns\HasJournalEntryActions;
use App\Enums\Accounting\TransactionType;
use App\Filament\Actions\CreateTransactionAction;
use App\Filament\Company\Pages\Service\ConnectedAccount;
use App\Filament\Company\Resources\Accounting\TransactionResource;
use App\Services\PlaidService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Width;

class ListTransactions extends ListRecords
{
    use HasJournalEntryActions;

    protected static string $resource = TransactionResource::class;

    public function getMaxContentWidth(): Width | string | null
    {
        return 'max-w-8xl';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                CreateTransactionAction::make('createDeposit')
                    ->label('Deposit')
                    ->type(TransactionType::Deposit),
                CreateTransactionAction::make('createWithdrawal')
                    ->label('Withdrawal')
                    ->type(TransactionType::Withdrawal),
                CreateTransactionAction::make('createTransfer')
                    ->label('Transfer')
                    ->type(TransactionType::Transfer),
                CreateTransactionAction::make('createJournalEntry')
                    ->label('Journal entry')
                    ->type(TransactionType::Journal),
            ])
                ->label('New transaction')
                ->button()
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-m-chevron-down')
                ->iconPosition(IconPosition::After),
            ActionGroup::make([
                Action::make('connectBank')
                    ->label('Connect your bank')
                    ->visible(app(PlaidService::class)->isEnabled())
                    ->url(ConnectedAccount::getUrl()),
            ])
                ->label('More')
                ->button()
                ->outlined()
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-m-chevron-down')
                ->iconPosition(IconPosition::After),
        ];
    }
}
