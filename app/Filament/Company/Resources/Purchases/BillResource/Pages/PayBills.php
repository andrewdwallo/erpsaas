<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\Pages;

use App\Enums\Accounting\BillStatus;
use App\Enums\Accounting\PaymentMethod;
use App\Filament\Company\Resources\Purchases\BillResource;
use App\Filament\Tables\Columns\CustomTextInputColumn;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Common\Vendor;
use App\Models\Setting\Currency;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * @property \Filament\Schemas\Schema $form
 */
class PayBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    protected string $view = 'filament.company.resources.purchases.bill-resource.pages.pay-bills';

    public array $paymentAmounts = [];

    public ?array $data = [];

    public function getBreadcrumb(): ?string
    {
        return 'Pay';
    }

    public function getTitle(): string | Htmlable
    {
        return 'Pay Bills';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill();

        $this->reset('tableFilters');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('processPayments')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirm payments')
                ->modalDescription(function () {
                    $billCount = collect($this->paymentAmounts)->filter(fn ($amount) => $amount > 0)->count();
                    $totalAmount = array_sum($this->paymentAmounts);
                    $currencyCode = $this->getTableFilterState('currency_code')['value'];
                    $totalFormatted = CurrencyConverter::formatCentsToMoney($totalAmount, $currencyCode, true);

                    return "You are about to pay {$billCount} " . Str::plural('bill', $billCount) . " for a total of {$totalFormatted}. This action cannot be undone.";
                })
                ->action(function () {
                    $data = $this->data;
                    $tableRecords = $this->getTableRecords();
                    $paidCount = 0;
                    $totalPaid = 0;

                    /** @var Bill $bill */
                    foreach ($tableRecords as $bill) {
                        if (! $bill->canRecordPayment()) {
                            continue;
                        }

                        // Get the payment amount from our component state
                        $paymentAmount = $this->getPaymentAmount($bill);

                        if ($paymentAmount <= 0) {
                            continue;
                        }

                        $paymentData = [
                            'posted_at' => $data['posted_at'],
                            'payment_method' => $data['payment_method'],
                            'bank_account_id' => $data['bank_account_id'],
                            'amount' => $paymentAmount,
                        ];

                        $bill->recordPayment($paymentData);
                        $paidCount++;
                        $totalPaid += $paymentAmount;
                    }

                    $currencyCode = $this->getTableFilterState('currency_code')['value'];
                    $totalFormatted = CurrencyConverter::formatCentsToMoney($totalPaid, $currencyCode, true);

                    Notification::make()
                        ->title('Bills paid successfully')
                        ->body("Paid {$paidCount} " . Str::plural('bill', $paidCount) . " for a total of {$totalFormatted}")
                        ->success()
                        ->send();

                    $this->reset('paymentAmounts');

                    $this->resetTable();
                }),
        ];
    }

    /**
     * @return array<int|string, string|\Filament\Schemas\Schema>
     */
    protected function getForms(): array
    {
        return [
            'form',
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->live()
            ->components([
                Grid::make(3)
                    ->schema([
                        Select::make('bank_account_id')
                            ->label('Account')
                            ->options(static function () {
                                return Transaction::getBankAccountOptionsFlat();
                            })
                            ->default(fn () => BankAccount::where('enabled', true)->first()?->id)
                            ->selectablePlaceholder(false)
                            ->searchable()
                            ->softRequired(),
                        DatePicker::make('posted_at')
                            ->label('Date')
                            ->default(now())
                            ->softRequired(),
                        Select::make('payment_method')
                            ->label('Payment method')
                            ->selectablePlaceholder(false)
                            ->options(PaymentMethod::class)
                            ->default(PaymentMethod::BankPayment)
                            ->softRequired(),
                    ]),
            ])->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Bill::query()
                    ->with(['vendor'])
                    ->unpaid()
            )
            ->recordClasses(['is-spreadsheet'])
            ->defaultSort('due_date')
            ->paginated(false)
            ->columns([
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->sortable(),
                TextColumn::make('bill_number')
                    ->label('Bill number')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due date')
                    ->defaultDateFormat()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount_due')
                    ->label('Amount due')
                    ->currency(static fn (Bill $record) => $record->currency_code)
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Summarizer::make()
                            ->using(function (Builder $query) {
                                $totalAmountDue = $query->sum('amount_due');
                                $bankAccountCurrency = $this->getSelectedBankAccount()->account->currency_code;
                                $activeCurrency = $this->getTableFilterState('currency_code')['value'] ?? $bankAccountCurrency;

                                if ($activeCurrency !== $bankAccountCurrency) {
                                    $totalAmountDue = CurrencyConverter::convertBalance($totalAmountDue, $activeCurrency, $bankAccountCurrency);
                                }

                                return CurrencyConverter::formatCentsToMoney($totalAmountDue, $bankAccountCurrency, true);
                            }),
                        Summarizer::make()
                            ->using(function (Builder $query) {
                                $totalAmountDue = $query->sum('amount_due');
                                $currencyCode = $this->getTableFilterState('currency_code')['value'];

                                return CurrencyConverter::formatCentsToMoney($totalAmountDue, $currencyCode, true);
                            })
                            ->visible(function () {
                                $activeCurrency = $this->getTableFilterState('currency_code')['value'] ?? null;
                                $bankAccountCurrency = $this->getSelectedBankAccount()->account->currency_code;

                                return $activeCurrency && $activeCurrency !== $bankAccountCurrency;
                            }),
                    ]),
                IconColumn::make('applyFullAmountAction')
                    ->icon('heroicon-m-chevron-double-right')
                    ->color('primary')
                    ->label('')
                    ->default('')
                    ->alignCenter()
                    ->width('3rem')
                    ->tooltip('Apply full amount')
                    ->action(
                        Action::make('applyFullPayment')
                            ->action(function (Bill $record) {
                                $this->paymentAmounts[$record->id] = $record->amount_due;
                            }),
                    ),
                CustomTextInputColumn::make('payment_amount')
                    ->label('Payment amount')
                    ->alignEnd()
                    ->navigable()
                    ->mask(RawJs::make('$money($input)'))
                    ->updateStateUsing(function (Bill $record, $state) {
                        if (! CurrencyConverter::isValidAmount($state, 'USD')) {
                            $this->paymentAmounts[$record->id] = 0;

                            return '0.00';
                        }

                        $paymentCents = CurrencyConverter::convertToCents($state, 'USD');

                        if ($paymentCents > $record->amount_due) {
                            $paymentCents = $record->amount_due;
                        }

                        $this->paymentAmounts[$record->id] = $paymentCents;

                        return $state;
                    })
                    ->getStateUsing(function (Bill $record) {
                        $paymentAmount = $this->paymentAmounts[$record->id] ?? 0;

                        return CurrencyConverter::convertCentsToFormatSimple($paymentAmount, 'USD');
                    })
                    ->summarize([
                        Summarizer::make()
                            ->using(function () {
                                $total = array_sum($this->paymentAmounts);
                                $defaultCurrency = CurrencyAccessor::getDefaultCurrency();
                                $activeCurrency = $this->getTableFilterState('currency_code')['value'] ?? $defaultCurrency;

                                if ($activeCurrency !== $defaultCurrency) {
                                    $total = CurrencyConverter::convertBalance($total, $activeCurrency, $defaultCurrency);
                                }

                                return CurrencyConverter::formatCentsToMoney($total, $defaultCurrency, true);
                            }),
                        Summarizer::make()
                            ->using(fn () => $this->totalPaymentAmount)
                            ->visible(function () {
                                $activeCurrency = $this->getTableFilterState('currency_code')['value'] ?? null;
                                $defaultCurrency = CurrencyAccessor::getDefaultCurrency();

                                return $activeCurrency && $activeCurrency !== $defaultCurrency;
                            }),
                    ]),
            ])
            ->toolbarActions([
                BulkAction::make('applyFullAmounts')
                    ->label('Apply full amounts')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $records->each(function (Bill $bill) {
                            $this->paymentAmounts[$bill->id] = $bill->amount_due;
                        });
                    }),
                BulkAction::make('clearAmounts')
                    ->label('Clear amounts')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $records->each(function (Bill $bill) {
                            $this->paymentAmounts[$bill->id] = 0;
                        });
                    }),
            ])
            ->filters([
                SelectFilter::make('currency_code')
                    ->label('Currency')
                    ->selectablePlaceholder(false)
                    ->default(CurrencyAccessor::getDefaultCurrency())
                    ->options(Currency::query()->pluck('name', 'code')->toArray())
                    ->searchable()
                    ->resetState([
                        'value' => CurrencyAccessor::getDefaultCurrency(),
                    ])
                    ->indicateUsing(function (SelectFilter $filter, array $state) {
                        if (blank($state['value'] ?? null)) {
                            return [];
                        }

                        $label = collect($filter->getOptions())
                            ->mapWithKeys(fn (string | array $label, string $value): array => is_array($label) ? $label : [$value => $label])
                            ->get($state['value']);

                        if (blank($label)) {
                            return [];
                        }

                        $indicator = $filter->getLabel();

                        return Indicator::make("{$indicator}: {$label}")->removable(false);
                    }),
                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->options(fn () => Vendor::query()->pluck('name', 'id')->toArray())
                    ->searchable(),
                SelectFilter::make('status')
                    ->multiple()
                    ->options(BillStatus::getUnpaidOptions()),
            ]);
    }

    protected function getPaymentAmount(Bill $record): int
    {
        return $this->paymentAmounts[$record->id] ?? 0;
    }

    #[Computed]
    public function totalPaymentAmount(): string
    {
        $total = array_sum($this->paymentAmounts);

        $currencyCode = $this->getTableFilterState('currency_code')['value'];

        return CurrencyConverter::formatCentsToMoney($total, $currencyCode, true);
    }

    public function getSelectedBankAccount(): BankAccount
    {
        $bankAccountId = $this->data['bank_account_id'];

        $bankAccount = BankAccount::find($bankAccountId);

        return $bankAccount ?: BankAccount::where('enabled', true)->first();
    }

    protected function handleTableFilterUpdates(): void
    {
        parent::handleTableFilterUpdates();

        $visibleBillIds = $this->getTableRecords()->pluck('id')->toArray();
        $visibleBillKeys = array_flip($visibleBillIds);

        $this->paymentAmounts = array_intersect_key($this->paymentAmounts, $visibleBillKeys);
    }
}
