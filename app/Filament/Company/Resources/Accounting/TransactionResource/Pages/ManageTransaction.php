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
            $this->createAction(
                name: 'addIncome',
                label: 'Add Income',
                type: 'deposit',
            ),
            $this->createAction(
                name: 'addExpense',
                label: 'Add Expense',
                type: 'withdrawal',
            ),
        ];
    }

    protected function createAction(string $name, string $label, string $type): Actions\CreateAction
    {
        return Actions\CreateAction::make($name)
            ->label($label)
            ->modalWidth(MaxWidth::ThreeExtraLarge)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->button()
            ->outlined()
            ->fillForm(static fn (): array => [
                'type' => $type,
                'posted_at' => now()->format('Y-m-d'),
                'bank_account_id' => BankAccount::where('enabled', true)->first()->id ?? null,
                'amount' => '0.00',
                'account_id' => static::getUncategorizedAccountByType($type)?->id,
            ]);
    }

    public static function getUncategorizedAccountByType(string $type): ?Account
    {
        [$category, $accountName] = match ($type) {
            'deposit' => [AccountCategory::Revenue, 'Uncategorized Income'],
            'withdrawal' => [AccountCategory::Expense, 'Uncategorized Expense'],
        };

        return Account::where('category', $category)
            ->where('name', $accountName)
            ->first();
    }
}
