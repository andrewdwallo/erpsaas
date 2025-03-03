<?php

use App\Facades\Accounting;
use App\Facades\Reporting;
use App\Factories\ReportDateFactory;
use App\Filament\Company\Pages\Reports\AccountBalances;
use App\Models\Accounting\Transaction;

use function Pest\Livewire\livewire;

it('correctly builds an account balances report for the current fiscal year', function () {
    $testCompany = $this->testCompany;

    $reportDates = ReportDateFactory::create($testCompany);
    $defaultDateRange = $reportDates->defaultDateRange;
    $defaultStartDate = $reportDates->defaultStartDate->toImmutable();
    $defaultEndDate = $reportDates->defaultEndDate->toImmutable();

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
            'posted_at' => $defaultEndDate,
        ])
        ->create();

    $defaultBankAccountAccount = $testCompany->default->bankAccount->account;

    $expectedBalances = Accounting::getBalances(
        $defaultBankAccountAccount,
        $defaultStartDate->toDateString(),
        $defaultEndDate->toDateString(),
    );

    $formattedExpectedBalances = Reporting::formatBalances($expectedBalances);

    livewire(AccountBalances::class)
        ->assertFormSet([
            'deferredFilters.dateRange' => $defaultDateRange,
            'deferredFilters.startDate' => $defaultStartDate->toDateTimeString(),
            'deferredFilters.endDate' => $defaultEndDate->toDateTimeString(),
        ])
        ->assertSet('filters', [
            'dateRange' => $defaultDateRange,
            'startDate' => $defaultStartDate->toDateString(),
            'endDate' => $defaultEndDate->toDateString(),
        ])
        ->call('applyFilters')
        ->assertSeeTextInOrder([
            $defaultBankAccountAccount->name,
            $formattedExpectedBalances->startingBalance,
            $formattedExpectedBalances->debitBalance,
            $formattedExpectedBalances->creditBalance,
            $formattedExpectedBalances->netMovement,
            $formattedExpectedBalances->endingBalance,
        ])
        ->assertReportTableData();
});

it('correctly builds an account balances report for the previous fiscal year', function () {
    $testCompany = $this->testCompany;

    $reportDatesDTO = ReportDateFactory::create($testCompany);
    $defaultDateRange = $reportDatesDTO->defaultDateRange;
    $defaultStartDate = $reportDatesDTO->defaultStartDate->toImmutable();
    $defaultEndDate = $reportDatesDTO->defaultEndDate->toImmutable();

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
            'posted_at' => $defaultEndDate,
        ])
        ->create();

    $defaultBankAccountAccount = $testCompany->default->bankAccount->account;

    $expectedBalancesSubYear = Accounting::getBalances(
        $defaultBankAccountAccount,
        $defaultStartDate->subYear()->startOfYear()->toDateString(),
        $defaultEndDate->subYear()->endOfYear()->toDateString(),
    );

    $formattedExpectedBalancesSubYear = Reporting::formatBalances($expectedBalancesSubYear);

    livewire(AccountBalances::class)
        ->assertFormSet([
            'deferredFilters.dateRange' => $defaultDateRange,
            'deferredFilters.startDate' => $defaultStartDate->toDateTimeString(),
            'deferredFilters.endDate' => $defaultEndDate->toDateTimeString(),
        ])
        ->assertSet('filters', [
            'dateRange' => $defaultDateRange,
            'startDate' => $defaultStartDate->toDateString(),
            'endDate' => $defaultEndDate->toDateString(),
        ])
        ->set('deferredFilters', [
            'startDate' => $defaultStartDate->subYear()->startOfYear()->toDateTimeString(),
            'endDate' => $defaultEndDate->subYear()->endOfYear()->toDateTimeString(),
        ])
        ->call('applyFilters')
        ->assertSeeTextInOrder([
            $defaultBankAccountAccount->name,
            $formattedExpectedBalancesSubYear->startingBalance,
            $formattedExpectedBalancesSubYear->debitBalance,
            $formattedExpectedBalancesSubYear->creditBalance,
            $formattedExpectedBalancesSubYear->netMovement,
            $formattedExpectedBalancesSubYear->endingBalance,
        ])
        ->assertReportTableData();
});
