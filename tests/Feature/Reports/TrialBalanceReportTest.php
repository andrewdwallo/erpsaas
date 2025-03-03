<?php

use App\Facades\Accounting;
use App\Facades\Reporting;
use App\Factories\ReportDateFactory;
use App\Filament\Company\Pages\Reports\TrialBalance;
use App\Models\Accounting\Transaction;

use function Pest\Livewire\livewire;

it('correctly builds a standard trial balance report', function () {
    $testCompany = $this->testCompany;

    $reportDates = ReportDateFactory::create($testCompany);
    $defaultDateRange = $reportDates->defaultDateRange;
    $defaultStartDate = $reportDates->defaultStartDate->toImmutable();
    $defaultEndDate = $reportDates->defaultEndDate->toImmutable();

    $defaultReportType = 'standard';

    $depositAmount = 1000;
    $withdrawalAmount = 1000;
    $depositCount = 10;
    $withdrawalCount = 10;

    Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit($depositAmount)
        ->count($depositCount)
        ->state([
            'posted_at' => $defaultStartDate->subWeek(),
        ])
        ->create();

    Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedExpense()
        ->asWithdrawal($withdrawalAmount)
        ->count($withdrawalCount)
        ->state([
            'posted_at' => now()->subWeek(),
        ])
        ->create();

    $defaultBankAccountAccount = $testCompany->default->bankAccount->account;
    $earliestTransactionDate = $reportDates->refresh()->earliestTransactionDate;

    $expectedBalances = Accounting::getBalances(
        $defaultBankAccountAccount,
        $earliestTransactionDate->toDateString(),
        $defaultEndDate->toDateString(),
    );

    $calculatedTrialBalances = Reporting::calculateTrialBalances($defaultBankAccountAccount->category, $expectedBalances['ending_balance']);

    $formattedExpectedBalances = Reporting::formatBalances($calculatedTrialBalances);

    livewire(TrialBalance::class)
        ->assertFormSet([
            'deferredFilters.reportType' => $defaultReportType,
            'deferredFilters.dateRange' => $defaultDateRange,
            'deferredFilters.asOfDate' => $defaultEndDate->toDateTimeString(),
        ])
        ->assertSet('filters', [
            'reportType' => $defaultReportType,
            'dateRange' => $defaultDateRange,
            'asOfDate' => $defaultEndDate->toDateString(),
        ])
        ->call('applyFilters')
        ->assertDontSeeText('Retained Earnings')
        ->assertSeeTextInOrder([
            $defaultBankAccountAccount->name,
            $formattedExpectedBalances->debitBalance,
            $formattedExpectedBalances->creditBalance,
        ])
        ->assertReportTableData();
});

it('correctly builds a post-closing trial balance report', function () {
    $testCompany = $this->testCompany;

    $reportDates = ReportDateFactory::create($testCompany);
    $defaultDateRange = $reportDates->defaultDateRange;
    $defaultStartDate = $reportDates->defaultStartDate->toImmutable();
    $defaultEndDate = $reportDates->defaultEndDate->toImmutable();

    $defaultReportType = 'postClosing';

    $depositAmount = 2000;
    $withdrawalAmount = 1000;
    $depositCount = 5;
    $withdrawalCount = 5;

    Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedRevenue()
        ->asDeposit($depositAmount)
        ->count($depositCount)
        ->state([
            'posted_at' => $defaultStartDate->subWeek(),
        ])
        ->create();

    Transaction::factory()
        ->forDefaultBankAccount()
        ->forUncategorizedExpense()
        ->asWithdrawal($withdrawalAmount)
        ->count($withdrawalCount)
        ->state([
            'posted_at' => now()->subWeek(),
        ])
        ->create();

    $defaultBankAccountAccount = $testCompany->default->bankAccount->account;
    $earliestTransactionDate = $reportDates->refresh()->earliestTransactionDate;

    $expectedBalances = Accounting::getBalances(
        $defaultBankAccountAccount,
        $earliestTransactionDate->toDateString(),
        $defaultEndDate->toDateString(),
    );

    $calculatedTrialBalances = Reporting::calculateTrialBalances($defaultBankAccountAccount->category, $expectedBalances['ending_balance']);

    $formattedExpectedBalances = Reporting::formatBalances($calculatedTrialBalances);

    $formattedRetainedEarningsBalances = Reporting::getRetainedEarningsBalances($earliestTransactionDate->toDateTimeString(), $defaultEndDate->toDateTimeString());

    // Use Livewire to assert the report's filters and displayed data
    livewire(TrialBalance::class)
        ->set('deferredFilters.reportType', $defaultReportType)
        ->assertFormSet([
            'deferredFilters.reportType' => $defaultReportType,
            'deferredFilters.dateRange' => $defaultDateRange,
            'deferredFilters.asOfDate' => $defaultEndDate->toDateTimeString(),
        ])
        ->call('applyFilters')
        ->assertSet('filters', [
            'reportType' => $defaultReportType,
            'dateRange' => $defaultDateRange,
            'asOfDate' => $defaultEndDate->toDateString(),
        ])
        ->assertSeeTextInOrder([
            $defaultBankAccountAccount->name,
            $formattedExpectedBalances->debitBalance,
            $formattedExpectedBalances->creditBalance,
        ])
        ->assertSeeText('Retained Earnings')
        ->assertSeeTextInOrder([
            'RE',
            'Retained Earnings',
            $formattedRetainedEarningsBalances->debitBalance,
            $formattedRetainedEarningsBalances->creditBalance,
        ])
        ->assertSeeTextInOrder([
            'Total Revenue',
            '$0.00',
            '$0.00',
        ])
        ->assertSeeTextInOrder([
            'Total Expenses',
            '$0.00',
            '$0.00',
        ])
        ->assertReportTableData();
});
