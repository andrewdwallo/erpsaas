<?php

use App\Enums\Accounting\JournalEntryType;
use App\Enums\Setting\EntityType;
use App\Filament\Company\Pages\CreateCompany;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\Company;

use function Pest\Livewire\livewire;

function createCompany(string $name): Company
{
    livewire(CreateCompany::class)
        ->fillForm([
            'name' => $name,
            'profile.email' => 'company@gmail.com',
            'profile.entity_type' => EntityType::LimitedLiabilityCompany,
            'profile.country' => 'US',
            'locale.language' => 'en',
            'currencies.code' => 'USD',
        ])
        ->call('register')
        ->assertHasNoErrors();

    return auth()->user()->currentCompany;
}

/**
 * Get the debit and credit accounts for a transaction.
 *
 * @return array<Account>
 */
function getTransactionDebitAndCreditAccounts(Transaction $transaction): array
{
    $debitAccount = $transaction->journalEntries->where('type', JournalEntryType::Debit)->firstOrFail()->account;
    $creditAccount = $transaction->journalEntries->where('type', JournalEntryType::Credit)->firstOrFail()->account;

    return [$debitAccount, $creditAccount];
}
