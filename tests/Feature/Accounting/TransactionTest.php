<?php

use App\Enums\Accounting\JournalEntryType;
use App\Enums\Accounting\TransactionType;
use App\Filament\Company\Pages\Accounting\Transactions;
use App\Filament\Forms\Components\JournalEntryRepeater;
use App\Filament\Tables\Actions\ReplicateBulkAction;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Utilities\Currency\ConfigureCurrencies;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ReplicateAction;

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
        ->journalEntries->sumDebits()->getValue()->toEqual($amount)
        ->journalEntries->sumCredits()->getValue()->toEqual($amount);
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
        ->amount->toEqual('1,500.00')
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getValue()->toEqual($expectedUSDValue)
        ->journalEntries->sumCredits()->getValue()->toEqual($expectedUSDValue);
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

    // The 1500 CAD is worth 1102.94 USD (1500 CAD / 1.36)
    $expectedUSDValue = round(1500 / 1.36, 2);

    // Verify that the debit is 1102.94 USD and the credit is 1500 CAD converted to 1102.94 USD
    // Transaction amount stays in source bank account currency (cast is applied)
    expect($transaction)
        ->amount->toEqual('1,500.00')
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getValue()->toEqual($expectedUSDValue)
        ->journalEntries->sumCredits()->getValue()->toEqual($expectedUSDValue);
});

it('handles multi-currency deposits correctly', function () {
    $foreignBankAccount = Account::factory()
        ->withForeignBankAccount('BHD Bank Account', 'BHD', 0.38)
        ->create();

    $foreignBankAccount->refresh();

    ConfigureCurrencies::syncCurrencies();

    // Create a deposit of 1500 BHD to the foreign bank account
    /** @var Transaction $transaction */
    $transaction = Transaction::factory()
        ->forBankAccount($foreignBankAccount->bankAccount)
        ->forUncategorizedRevenue()
        ->asDeposit(1500)
        ->create();

    [$debitAccount, $creditAccount] = getTransactionDebitAndCreditAccounts($transaction);

    expect($debitAccount->is($foreignBankAccount))->toBeTrue() // Debit: Foreign Bank Account (BHD) account
        ->and($creditAccount->name)->toBe('Uncategorized Income'); // Credit: Uncategorized Income (USD) account

    // Convert to USD using the rate 0.38 BHD per USD
    $expectedUSDValue = round(1500 / 0.38, 2);

    // Verify that the debit is 39473.68 USD and the credit is 1500 BHD converted to 39473.68 USD
    expect($transaction)
        ->amount->toEqual('1,500.000')
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getValue()->toEqual($expectedUSDValue)
        ->journalEntries->sumCredits()->getValue()->toEqual($expectedUSDValue);
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

    $expectedUSDValue = round(1500 / 0.76, 2);

    expect($transaction)
        ->amount->toEqual('1,500.00')
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getValue()->toEqual($expectedUSDValue)
        ->journalEntries->sumCredits()->getValue()->toEqual($expectedUSDValue);
});

it('can add an income or expense transaction', function (TransactionType $transactionType, string $actionName) {
    $testCompany = $this->testCompany;
    $defaultBankAccount = $testCompany->default->bankAccount;
    $defaultAccount = Transactions::getUncategorizedAccountByType($transactionType);

    livewire(Transactions::class)
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
        ->amount->toEqual('500.00')
        ->type->toBe($transactionType)
        ->bankAccount->is($defaultBankAccount)->toBeTrue()
        ->account->is($defaultAccount)->toBeTrue()
        ->journalEntries->count()->toBe(2);
})->with([
    [TransactionType::Deposit, 'addIncome'],
    [TransactionType::Withdrawal, 'addExpense'],
]);

it('can add a transfer transaction', function () {
    $testCompany = $this->testCompany;
    $sourceBankAccount = $testCompany->default->bankAccount;
    $destinationBankAccount = Account::factory()->withBankAccount('Destination Bank Account')->create();

    livewire(Transactions::class)
        ->mountAction('addTransfer')
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
        ->amount->toEqual('1,500.00')
        ->type->toBe(TransactionType::Transfer)
        ->bankAccount->is($sourceBankAccount)->toBeTrue()
        ->account->is($destinationBankAccount)->toBeTrue()
        ->journalEntries->count()->toBe(2);
});

it('can add a journal transaction', function () {
    $defaultDebitAccount = Transactions::getUncategorizedAccountByType(TransactionType::Withdrawal);
    $defaultCreditAccount = Transactions::getUncategorizedAccountByType(TransactionType::Deposit);

    $undoRepeaterFake = JournalEntryRepeater::fake();

    livewire(Transactions::class)
        ->mountAction('addJournalTransaction')
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
        ->amount->toEqual('1,000.00')
        ->type->isJournal()->toBeTrue()
        ->bankAccount->toBeNull()
        ->account->toBeNull()
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getValue()->toEqual(1000)
        ->journalEntries->sumCredits()->getValue()->toEqual(1000)
        ->and($debitAccount->is($defaultDebitAccount))->toBeTrue()
        ->and($creditAccount->is($defaultCreditAccount))->toBeTrue();
});

it('can update a deposit or withdrawal transaction', function (TransactionType $transactionType) {
    $defaultAccount = Transactions::getUncategorizedAccountByType($transactionType);

    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forAccount($defaultAccount)
        ->forType($transactionType, 1000)
        ->create();

    $newDescription = 'Updated Description';

    livewire(Transactions::class)
        ->mountTableAction('editTransaction', $transaction)
        ->assertTableActionDataSet([
            'type' => $transactionType->value,
            'description' => $transaction->description,
            'amount' => $transaction->amount,
        ])
        ->setTableActionData([
            'description' => $newDescription,
            'amount' => '1,500.00',
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $transaction->refresh();

    expect($transaction->description)->toBe($newDescription)
        ->and($transaction->amount)->toEqual('1,500.00');
})->with([
    TransactionType::Deposit,
    TransactionType::Withdrawal,
]);

it('does not show Edit Transfer or Edit Journal Transaction for deposit or withdrawal transactions', function (TransactionType $transactionType) {
    $defaultAccount = Transactions::getUncategorizedAccountByType($transactionType);

    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forAccount($defaultAccount)
        ->forType($transactionType, 1000)
        ->create();

    livewire(Transactions::class)
        ->assertTableActionHidden('editTransfer', $transaction)
        ->assertTableActionHidden('editJournalTransaction', $transaction);
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

    livewire(Transactions::class)
        ->mountTableAction('editTransfer', $transaction)
        ->assertTableActionDataSet([
            'type' => TransactionType::Transfer->value,
            'description' => $transaction->description,
            'amount' => $transaction->amount,
        ])
        ->setTableActionData([
            'description' => $newDescription,
            'amount' => '2,000.00',
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $transaction->refresh();

    expect($transaction->description)->toBe($newDescription)
        ->and($transaction->amount)->toEqual('2,000.00');
});

it('does not show Edit Transaction or Edit Journal Transaction for transfer transactions', function () {
    $transaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forDestinationBankAccount()
        ->asTransfer(1500)
        ->create();

    livewire(Transactions::class)
        ->assertTableActionHidden('editTransaction', $transaction)
        ->assertTableActionHidden('editJournalTransaction', $transaction);
});

it('replicates a transaction with correct journal entries', function () {
    $originalTransaction = Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit(1000)
        ->create();

    livewire(Transactions::class)
        ->callTableAction(ReplicateAction::class, $originalTransaction);

    $replicatedTransaction = Transaction::whereKeyNot($originalTransaction->getKey())->first();

    expect($replicatedTransaction)->not->toBeNull();

    [$originalDebitAccount, $originalCreditAccount] = getTransactionDebitAndCreditAccounts($originalTransaction);

    [$replicatedDebitAccount, $replicatedCreditAccount] = getTransactionDebitAndCreditAccounts($replicatedTransaction);

    expect($replicatedTransaction)
        ->journalEntries->count()->toBe(2)
        ->journalEntries->sumDebits()->getValue()->toEqual(1000)
        ->journalEntries->sumCredits()->getValue()->toEqual(1000)
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

    livewire(Transactions::class)
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
            ->journalEntries->sumDebits()->getValue()->toEqual(1000)
            ->journalEntries->sumCredits()->getValue()->toEqual(1000)
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

    livewire(Transactions::class)
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

    livewire(Transactions::class)
        ->callTableBulkAction(DeleteBulkAction::class, $transactions);

    $this->assertDatabaseEmpty('transactions');
    $this->assertDatabaseEmpty('journal_entries');
});
