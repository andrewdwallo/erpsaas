<?php

namespace App\Filament\Company\Pages\Reports;

use App\Contracts\ExportableReport;
use App\DTO\ReportDTO;
use App\Filament\Company\Resources\Accounting\TransactionResource;
use App\Models\Accounting\Account;
use App\Models\Common\Client;
use App\Models\Common\Vendor;
use App\Services\ExportService;
use App\Services\ReportService;
use App\Support\Column;
use App\Transformers\AccountTransactionReportTransformer;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountTransactions extends BaseReportPage
{
    protected string $view = 'filament.company.pages.reports.account-transactions';

    protected ReportService $reportService;

    protected ExportService $exportService;

    public function boot(ReportService $reportService, ExportService $exportService): void
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return 'max-w-8xl';
    }

    protected function initializeDefaultFilters(): void
    {
        if (empty($this->getFilterState('selectedAccount'))) {
            $this->setFilterState('selectedAccount', 'all');
        }

        if (empty($this->getFilterState('selectedPayee'))) {
            $this->setFilterState('selectedPayee', 'all');
        }
    }

    /**
     * @return array<Column>
     */
    public function getTable(): array
    {
        return [
            Column::make('date')
                ->label('DATE')
                ->markAsDate()
                ->alignment(Alignment::Left),
            Column::make('description')
                ->label('DESCRIPTION')
                ->alignment(Alignment::Left),
            Column::make('debit')
                ->label('DEBIT')
                ->alignment(Alignment::Right),
            Column::make('credit')
                ->label('CREDIT')
                ->alignment(Alignment::Right),
            Column::make('balance')
                ->label('RUNNING BALANCE')
                ->alignment(Alignment::Right),
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->columns(5)
            ->components([
                Select::make('selectedAccount')
                    ->label('Account')
                    ->options($this->getAccountOptions())
                    ->selectablePlaceholder(false)
                    ->searchable(),
                $this->getDateRangeFormComponent(),
                Cluster::make([
                    $this->getStartDateFormComponent(),
                    $this->getEndDateFormComponent(),
                ])->extraFieldWrapperAttributes([
                    'class' => 'report-hidden-label',
                ]),
                Select::make('selectedPayee')
                    ->label('Payee')
                    ->options($this->getEntityOptions())
                    ->searchable()
                    ->selectablePlaceholder(false),
                Actions::make([
                    Action::make('applyFilters')
                        ->label('Update report')
                        ->action('applyFilters')
                        ->keyBindings(['mod+s'])
                        ->button(),
                ])->alignEnd()->verticallyAlignEnd(),
            ]);
    }

    protected function getAccountOptions(): array
    {
        $accounts = Account::query()
            ->get()
            ->groupBy(fn (Account $account) => $account->category->getPluralLabel())
            ->map(fn (Collection $accounts) => $accounts->pluck('name', 'id'))
            ->toArray();

        $allAccountsOption = [
            'All Accounts' => ['all' => 'All Accounts'],
        ];

        return $allAccountsOption + $accounts;
    }

    protected function getEntityOptions(): array
    {
        $clients = Client::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $vendors = Vendor::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [-$id => $name])
            ->toArray();

        $allEntitiesOption = [
            'All Payees' => ['all' => 'All Payees'],
        ];

        return $allEntitiesOption + [
            'Clients' => $clients,
            'Vendors' => $vendors,
        ];
    }

    protected function buildReport(array $columns): ReportDTO
    {
        return $this->reportService->buildAccountTransactionsReport(
            startDate: $this->getFormattedStartDate(),
            endDate: $this->getFormattedEndDate(),
            columns: $columns,
            accountId: $this->getFilterState('selectedAccount'),
            entityId: $this->getFilterState('selectedPayee'),
        );
    }

    protected function getTransformer(ReportDTO $reportDTO): ExportableReport
    {
        return new AccountTransactionReportTransformer($reportDTO);
    }

    public function exportCSV(): StreamedResponse
    {
        return $this->exportService->exportToCsv($this->company, $this->report, $this->getFilterState('startDate'), $this->getFilterState('endDate'));
    }

    public function exportPDF(): StreamedResponse
    {
        return $this->exportService->exportToPdf($this->company, $this->report, $this->getFilterState('startDate'), $this->getFilterState('endDate'));
    }

    public function getEmptyStateHeading(): string | Htmlable
    {
        return 'No Transactions Found';
    }

    public function getEmptyStateDescription(): string | Htmlable | null
    {
        return 'Adjust the account or date range, or start by creating a transaction.';
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-x-mark';
    }

    public function getEmptyStateActions(): array
    {
        return [
            Action::make('createTransaction')
                ->label('Create transaction')
                ->url(TransactionResource::getUrl()),
        ];
    }

    public function tableHasEmptyState(): bool
    {
        return empty($this->report?->getCategories());
    }
}
