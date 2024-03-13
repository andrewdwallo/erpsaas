<?php

namespace App\Filament\Company\Resources\Accounting\TransactionResource\Pages;

use App\Enums\Accounting\AccountCategory;
use App\Filament\Company\Resources\Accounting\TransactionResource;
use App\Models\Accounting\Account;
use App\Models\Banking\BankAccount;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageTransaction extends ManageRecords
{
    public static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make('addIncome')
                ->label('Add Income')
                ->modalWidth(MaxWidth::ThreeExtraLarge)
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->button()
                ->outlined()
                ->fillForm(static fn (): array => [
                    'method' => 'deposit',
                    'posted_at' => now()->format('Y-m-d'),
                    'bank_account_id' => BankAccount::first()->isEnabled() ? BankAccount::first()->id : null,
                    'amount' => '0.00',
                    'account_id' => Account::where('category', AccountCategory::Revenue)->where('name', 'Uncategorized Income')->first()->id,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $method = $data['method'];

                    if ($method === 'deposit') {
                        $data['type'] = 'income';
                    } else {
                        $data['type'] = 'expense';
                    }

                    return $data;
                }),
            Actions\CreateAction::make('addExpense')
                ->label('Add Expense')
                ->modalWidth(MaxWidth::ThreeExtraLarge)
                ->stickyModalHeader()
                ->stickyModalFooter()
                ->button()
                ->outlined()
                ->fillForm(static fn (): array => [
                    'method' => 'withdrawal',
                    'posted_at' => now()->format('Y-m-d'),
                    'bank_account_id' => BankAccount::first()->isEnabled() ? BankAccount::first()->id : null,
                    'amount' => '0.00',
                    'account_id' => Account::where('category', AccountCategory::Expense)->where('name', 'Uncategorized Expense')->first()->id,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $method = $data['method'];

                    if ($method === 'deposit') {
                        $data['type'] = 'income';
                    } else {
                        $data['type'] = 'expense';
                    }

                    return $data;
                }),
        ];
    }
}
