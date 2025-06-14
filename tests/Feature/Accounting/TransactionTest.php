<?php

use App\Enums\Accounting\JournalEntryType;
use App\Enums\Accounting\TransactionType;
use App\Filament\Company\Resources\Accounting\TransactionResource\Pages\ListTransactions;
use App\Filament\Forms\Components\JournalEntryRepeater;
use App\Filament\Tables\Actions\EditTransactionAction;
use App\Filament\Tables\Actions\ReplicateBulkAction;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Utilities\Currency\ConfigureCurrencies;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ReplicateAction;

use function Pest\Livewire\livewire;

it('creates correct journal entries for a deposit transaction', function () {
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit(1000)
        ->create();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    expect($transaction->journalEntries->count())->toBe(2)
        ->and($debitAccount->name)->toBe('Cash on Hand')
        ->and($creditAccount->name)->toBe('Uncategorized Income');
});

it('creates correct journal entries for a withdrawal transaction', function () {
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedExpense()
        ->asWithdrawal(500)
        ->create();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    expect($transaction->journalEntries->count())->toBe(2)
        ->and($debitAccount->name)->toBe('Uncategorized Expense')
        ->and($creditAccount->name)->toBe('Cash on Hand');
});

it('creates correct journal entries for a transfer transaction', function () {
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forDestinationBankAccount()
        ->asTransfer(1500)
        ->create();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    // Acts as a withdrawal transaction for the source account
    expect($transaction->journalEntries->count())->toBe(2)
        ->and($debitAccount->name)->toBe('Destination Bank Account')
        ->and($creditAccount->name)->toBe('Cash on Hand');
});

it('does not create journal entries for a journal transaction', function () {
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asJournal(1000)
        ->create();

    // Journal entries for a journal transaction are created manually
    expect($transaction->journalEntries->count())->toBe(0);
});

it('stores and sums correct debit and credit amounts for different transaction types', function ($method, $setupMethod, $amount) {
    /** @var Transaction $transaction */
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->{$setupMethod}()
        ->{$method}($amount)
        ->create();

    expect($transaction)
        ->journalEntries->sumDebits()->getAmount()->toEqual($amount)
        ->journalEntries->sumCredits()->getAmount()->toEqual($amount);
})->with([
    ['asDeposit', 'forUncategorizedRevenue', 2000],
    ['asWithdrawal', 'forUncategorizedExpense', 500],
    ['asTransfer', 'forDestinationBankAccount', 1500],
]);

it('deletes associated journal entries when transaction is deleted', function () {
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit(1000)
        ->create();

    expect($transaction->journalEntries()->count())->toBe(2);

    $transaction->delete();

    $this->assertModelMissing($transaction);

    $this->assertDatabaseCount('journal_entries', 0);
});

it('handles multi-currency transfers without conversion when the source bank account is in the default currency', function () {
    $foreignBankAccount = Account::factory()
        ->withForeignBankAccount('Foreign Bank Account', 'EUR', 0.92)
        ->create();

    /** @var Transaction $transaction */
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forDestinationBankAccount($foreignBankAccount)
        ->asTransfer(1500)
        ->create();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    expect($debitAccount->is($foreignBankAccount))->toBeTrue()
        ->and($creditAccount->name)->toBe('Cash on Hand');

    $expectedUSDValue = 1500;

    expect($transaction)
        ->amount->toBe(1500)
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getAmount()->toEqual($expectedUSDValue)
        ->journalEntries->sumCredits()->getAmount()->toEqual($expectedUSDValue);
});

it('handles multi-currency transfers correctly', function () {
    $foreignBankAccount = Account::factory()
        ->withForeignBankAccount('CAD Bank Account', 'CAD', 1.36)
        ->create();

    $foreignBankAccount->refresh();

    ConfigureCurrencies::syncCurrencies();

    // Create a transfer of 1500 CAD from the foreign bank account to USD bank account
    /** @var Transaction $transaction */
    $transaction = Transaction::factory()
        ->forBankAccount($foreignBankAccount->bankAccount)
        ->forDestinationBankAccount()
        ->asTransfer(1500)
        ->create();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    expect($debitAccount->name)->toBe('Destination Bank Account') // Debit: Destination (USD) account
        ->and($creditAccount->is($foreignBankAccount))->toBeTrue(); // Credit: Foreign Bank Account (CAD) account

    // The 1500 CAD is worth approximately 1103 USD (1500 CAD / 1.36)
    $expectedUSDValue = CurrencyConverter::convertBalance(1500, 'CAD', 'USD');

    // Verify that the debit and credit are converted to USD cents
    // Transaction amount stays in source bank account currency
    expect($transaction)
        ->amount->toBe(1500)
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getAmount()->toEqual($expectedUSDValue)
        ->journalEntries->sumCredits()->getAmount()->toEqual($expectedUSDValue);
});

it('handles multi-currency deposits correctly', function () {
    $foreignBankAccount = Account::factory()
        ->withForeignBankAccount('BHD Bank Account', 'BHD', 0.38)
        ->create();

    $foreignBankAccount->refresh();

    ConfigureCurrencies::syncCurrencies();

    // Create a deposit of 1500 BHD (in fils - BHD subunits) to the foreign bank account
    /** @var Transaction $transaction */
    $transaction = Transaction::factory()
        ->forBankAccount($foreignBankAccount->bankAccount)
        ->forUncategorizedRevenue()
        ->asDeposit(1500)
        ->create();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    expect($debitAccount->is($foreignBankAccount))->toBeTrue() // Debit: Foreign Bank Account (BHD) account
        ->and($creditAccount->name)->toBe('Uncategorized Income'); // Credit: Uncategorized Income (USD) account

    $expectedUSDValue = CurrencyConverter::convertBalance(1500, 'BHD', 'USD');

    // Verify that journal entries are converted to USD cents
    expect($transaction)
        ->amount->toBe(1500)
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getAmount()->toEqual($expectedUSDValue)
        ->journalEntries->sumCredits()->getAmount()->toEqual($expectedUSDValue);
});

it('handles multi-currency withdrawals correctly', function () {
    $foreignBankAccount = Account::factory()
        ->withForeignBankAccount('Foreign Bank Account', 'GBP', 0.76) // GBP account
        ->create();

    $foreignBankAccount->refresh();

    ConfigureCurrencies::syncCurrencies();

    /** @var Transaction $transaction */
    $transaction = Transaction::factory()
        ->forBankAccount($foreignBankAccount->bankAccount)
        ->forUncategorizedExpense()
        ->asWithdrawal(1500)
        ->create();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    expect($debitAccount->name)->toBe('Uncategorized Expense')
        ->and($creditAccount->is($foreignBankAccount))->toBeTrue();

    $expectedUSDValue = CurrencyConverter::convertBalance(1500, 'GBP', 'USD');

    expect($transaction)
        ->amount->toBe(1500)
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getAmount()->toEqual($expectedUSDValue)
        ->journalEntries->sumCredits()->getAmount()->toEqual($expectedUSDValue);
});

it('can add an income or expense transaction', function (TransactionType $transactionType, string $actionName) {
    $testCompany = $this->testCompany;
    $defaultBankAccount = $testCompany->default->bankAccount;
    $defaultAccount = Transaction::getUncategorizedAccountByType($transactionType);

    livewire(ListTransactions::class)
        ->mountAction($actionName)
        ->assertActionDataSet([
            'posted_at' => today(),
            'type' => $transactionType,
            'bank_account_id' => $defaultBankAccount->id,
            'amount' => '0.00',
            'account_id' => $defaultAccount->id,
        ])
        ->setActionData([
            'amount' => '500.00',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $transaction = Transaction::first();

    expect($transaction)
        ->not->toBeNull()
        ->amount->toBe(50000) // 500.00 in cents
        ->type->toBe($transactionType)
        ->bankAccount->is($defaultBankAccount)->toBeTrue()
        ->account->is($defaultAccount)->toBeTrue()
        ->journalEntries->count()->toBe(2);
})->with([
    [TransactionType::Deposit, 'createDeposit'],
    [TransactionType::Withdrawal, 'createWithdrawal'],
]);

it('can add a transfer transaction', function () {
    $testCompany = $this->testCompany;
    $sourceBankAccount = $testCompany->default->bankAccount;
    $destinationBankAccount = Account::factory()->withBankAccount('Destination Bank Account')->create();

    livewire(ListTransactions::class)
        ->mountAction('createTransfer')
        ->assertActionDataSet([
            'posted_at' => today(),
            'type' => TransactionType::Transfer,
            'bank_account_id' => $sourceBankAccount->id,
            'amount' => '0.00',
            'account_id' => null,
        ])
        ->setActionData([
            'account_id' => $destinationBankAccount->id,
            'amount' => '1,500.00',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $transaction = Transaction::first();

    expect($transaction)
        ->not->toBeNull()
        ->amount->toBe(150000) // 1,500.00 in cents
        ->type->toBe(TransactionType::Transfer)
        ->bankAccount->is($sourceBankAccount)->toBeTrue()
        ->account->is($destinationBankAccount)->toBeTrue()
        ->journalEntries->count()->toBe(2);
});

it('can add a journal transaction', function () {
    $defaultDebitAccount = Transaction::getUncategorizedAccountByType(TransactionType::Withdrawal);
    $defaultCreditAccount = Transaction::getUncategorizedAccountByType(TransactionType::Deposit);

    $undoRepeaterFake = JournalEntryRepeater::fake();

    livewire(ListTransactions::class)
        ->mountAction('createJournalEntry')
        ->assertActionDataSet([
            'posted_at' => today(),
            'journalEntries' => [
                ['type' => JournalEntryType::Debit, 'account_id' => $defaultDebitAccount->id, 'amount' => '0.00'],
                ['type' => JournalEntryType::Credit, 'account_id' => $defaultCreditAccount->id, 'amount' => '0.00'],
            ],
        ])
        ->setActionData([
            'journalEntries' => [
                ['amount' => '1,000.00'],
                ['amount' => '1,000.00'],
            ],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $undoRepeaterFake();

    $transaction = Transaction::first();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    expect($transaction)
        ->not->toBeNull()
        ->amount->toBe(100000) // 1,000.00 in cents
        ->type->isJournal()->toBeTrue()
        ->bankAccount->toBeNull()
        ->account->toBeNull()
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getAmount()->toEqual(100000)
        ->journalEntries->sumCredits()->getAmount()->toEqual(100000)
        ->and($debitAccount->is($defaultDebitAccount))->toBeTrue()
        ->and($creditAccount->is($defaultCreditAccount))->toBeTrue();
});

it('can update a deposit or withdrawal transaction', function (TransactionType $transactionType) {
    $defaultAccount = Transaction::getUncategorizedAccountByType($transactionType);

    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forAccount($defaultAccount)
        ->forType($transactionType, 1000)
        ->create();

    $newDescription = 'Updated Description';

    $formattedAmount = CurrencyConverter::convertCentsToFormatSimple($transaction->amount);

    livewire(ListTransactions::class)
        ->mountTableAction(EditTransactionAction::class, $transaction)
        ->assertTableActionDataSet([
            'type' => $transactionType->value,
            'description' => $transaction->description,
            'amount' => $formattedAmount,
        ])
        ->setTableActionData([
            'description' => $newDescription,
            'amount' => '1,500.00',
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $transaction->refresh();

    expect($transaction->description)->toBe($newDescription)
        ->and($transaction->amount)->toBe(150000); // 1,500.00 in cents
})->with([
    TransactionType::Deposit,
    TransactionType::Withdrawal,
]);

it('can update a transfer transaction', function () {
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forDestinationBankAccount()
        ->asTransfer(1500)
        ->create();

    $newDescription = 'Updated Transfer Description';

    $formattedAmount = CurrencyConverter::convertCentsToFormatSimple($transaction->amount);

    livewire(ListTransactions::class)
        ->mountTableAction(EditTransactionAction::class, $transaction)
        ->assertTableActionDataSet([
            'type' => TransactionType::Transfer->value,
            'description' => $transaction->description,
            'amount' => $formattedAmount,
        ])
        ->setTableActionData([
            'description' => $newDescription,
            'amount' => '2,000.00',
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $transaction->refresh();

    expect($transaction->description)->toBe($newDescription)
        ->and($transaction->amount)->toBe(200000); // 2,000.00 in cents
});

it('replicates a transaction with correct journal entries', function () {
    $originalTransaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit(1000)
        ->create();

    livewire(ListTransactions::class)
        ->callTableAction(ReplicateAction::class, $originalTransaction);

    $replicatedTransaction = Transaction::whereKeyNot($originalTransaction->getKey())->first();

    expect($replicatedTransaction)->not->toBeNull();

    [$originalDebitAccount, $originalCreditAccount] = getTransactionDebitAndCreditAccounts($originalTransaction);

    [$replicatedDebitAccount, $replicatedCreditAccount] = getTransactionDebitAndCreditAccounts($replicatedTransaction);

    expect($replicatedTransaction)
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getAmount()->toEqual(1000)
        ->journalEntries->sumCredits()->getAmount()->toEqual(1000)
        ->description->toBe('(Copy of) ' . $originalTransaction->description)
        ->and($replicatedDebitAccount->name)->toBe($originalDebitAccount->name)
        ->and($replicatedCreditAccount->name)->toBe($originalCreditAccount->name);
});

it('bulk replicates transactions with correct journal entries', function () {
    $originalTransactions = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit(1000)
        ->count(3)
        ->create();

    livewire(ListTransactions::class)
        ->callTableBulkAction(ReplicateBulkAction::class, $originalTransactions);

    $replicatedTransactions = Transaction::whereKeyNot($originalTransactions->modelKeys())->get();

    expect($replicatedTransactions->count())->toBe(3);

    $originalTransactions->each(function (Transaction $originalTransaction) use ($replicatedTransactions) {
        /** @var Transaction $replicatedTransaction */
        $replicatedTransaction = $replicatedTransactions->firstWhere('description', '(Copy of) ' . $originalTransaction->description);

        expect($replicatedTransaction)->not->toBeNull();

        [$originalDebitAccount, $originalCreditAccount] = getTransactionDebitAndCreditAccounts($originalTransaction);

        [$replicatedDebitAccount, $replicatedCreditAccount] = getTransactionDebitAndCreditAccounts($replicatedTransaction);

        expect($replicatedTransaction)
            ->journalEntries->count()->toBe(2)
            ->journalEntries->sumDebits()->getAmount()->toEqual(1000)
            ->journalEntries->sumCredits()->getAmount()->toEqual(1000)
            ->and($replicatedDebitAccount->name)->toBe($originalDebitAccount->name)
            ->and($replicatedCreditAccount->name)->toBe($originalCreditAccount->name);
    });
});

it('can delete a transaction with journal entries', function () {
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit(1000)
        ->create();

    expect($transaction->journalEntries()->count())->toBe(2);

    livewire(ListTransactions::class)
        ->callTableAction(DeleteAction::class, $transaction);

    $this->assertModelMissing($transaction);

    $this->assertDatabaseEmpty('journal_entries');
});

it('can bulk delete transactions with journal entries', function () {
    $transactions = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit(1000)
        ->count(3)
        ->create();

    expect($transactions->count())->toBe(3);

    livewire(ListTransactions::class)
        ->callTableBulkAction(DeleteBulkAction::class, $transactions);

    $this->assertDatabaseEmpty('transactions');
    $this->assertDatabaseEmpty('journal_entries');
});
