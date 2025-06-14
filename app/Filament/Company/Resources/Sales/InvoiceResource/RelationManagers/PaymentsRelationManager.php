<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\RelationManagers;

use App\Enums\Accounting\InvoiceStatus;
use App\Enums\Accounting\PaymentMethod;
use App\Enums\Accounting\TransactionType;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $modelLabel = 'Payment';

    protected static bool $isLazy = false;

    protected $listeners = [
        'refresh' => '$refresh',
    ];

    public function isReadOnly(): bool
    {
        return false;
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->status !== InvoiceStatus::Draft;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                DatePicker::make('posted_at')
                    ->label('Date'),
                Grid::make()
                    ->schema([
                        Select::make('bank_account_id')
                            ->label('Account')
                            ->required()
                            ->live()
                            ->options(function () {
                                return BankAccount::query()
                                    ->join('accounts', 'bank_accounts.account_id', '=', 'accounts.id')
                                    ->select(['bank_accounts.id', 'accounts.name', 'accounts.currency_code'])
                                    ->get()
                                    ->mapWithKeys(function ($account) {
                                        $label = $account->name;
                                        if ($account->currency_code) {
                                            $label .= " ({$account->currency_code})";
                                        }

                                        return [$account->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable(),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->money(function (RelationManager $livewire) {
                                /** @var Invoice $invoice */
                                $invoice = $livewire->getOwnerRecord();

                                return $invoice->currency_code;
                            })
                            ->live(onBlur: true)
                            ->helperText(function (RelationManager $livewire, $state, ?Transaction $record) {
                                /** @var Invoice $ownerRecord */
                                $ownerRecord = $livewire->getOwnerRecord();

                                $invoiceCurrency = $ownerRecord->currency_code;

                                if (! CurrencyConverter::isValidAmount($state, 'USD')) {
                                    return null;
                                }

                                $amountDue = $ownerRecord->amount_due;

                                $amount = CurrencyConverter::convertToCents($state, 'USD');

                                if ($amount <= 0) {
                                    return 'Please enter a valid positive amount';
                                }

                                $currentPaymentAmount = $record?->amount ?? 0;

                                if ($ownerRecord->status === InvoiceStatus::Overpaid) {
                                    $newAmountDue = $amountDue + $amount - $currentPaymentAmount;
                                } else {
                                    $newAmountDue = $amountDue - $amount + $currentPaymentAmount;
                                }

                                return match (true) {
                                    $newAmountDue > 0 => 'Amount due after payment will be ' . CurrencyConverter::formatCentsToMoney($newAmountDue, $invoiceCurrency),
                                    $newAmountDue === 0 => 'Invoice will be fully paid',
                                    default => 'Invoice will be overpaid by ' . CurrencyConverter::formatCentsToMoney(abs($newAmountDue), $invoiceCurrency),
                                };
                            })
                            ->rules([
                                static fn (): Closure => static function (string $attribute, $value, Closure $fail) {
                                    if (! CurrencyConverter::isValidAmount($value, 'USD')) {
                                        $fail('Please enter a valid amount');
                                    }
                                },
                            ]),
                    ])->columns(2),
                Placeholder::make('currency_conversion')
                    ->label('Currency Conversion')
                    ->content(function (Get $get, RelationManager $livewire) {
                        $amount = $get('amount');
                        $bankAccountId = $get('bank_account_id');

                        /** @var Invoice $invoice */
                        $invoice = $livewire->getOwnerRecord();
                        $invoiceCurrency = $invoice->currency_code;

                        if (empty($amount) || empty($bankAccountId) || ! CurrencyConverter::isValidAmount($amount, 'USD')) {
                            return null;
                        }

                        $bankAccount = BankAccount::with('account')->find($bankAccountId);
                        if (! $bankAccount) {
                            return null;
                        }

                        $bankCurrency = $bankAccount->account->currency_code ?? CurrencyAccessor::getDefaultCurrency();

                        // If currencies are the same, no conversion needed
                        if ($invoiceCurrency === $bankCurrency) {
                            return null;
                        }

                        // Convert amount from invoice currency to bank currency
                        $amountInInvoiceCurrencyCents = CurrencyConverter::convertToCents($amount, 'USD');
                        $amountInBankCurrencyCents = CurrencyConverter::convertBalance(
                            $amountInInvoiceCurrencyCents,
                            $invoiceCurrency,
                            $bankCurrency
                        );

                        $formattedBankAmount = CurrencyConverter::formatCentsToMoney($amountInBankCurrencyCents, $bankCurrency);

                        return "Payment will be recorded as {$formattedBankAmount} in the bank account's currency ({$bankCurrency}).";
                    })
                    ->hidden(function (Get $get, RelationManager $livewire) {
                        $bankAccountId = $get('bank_account_id');
                        if (empty($bankAccountId)) {
                            return true;
                        }

                        /** @var Invoice $invoice */
                        $invoice = $livewire->getOwnerRecord();
                        $invoiceCurrency = $invoice->currency_code;

                        $bankAccount = BankAccount::with('account')->find($bankAccountId);
                        if (! $bankAccount) {
                            return true;
                        }

                        $bankCurrency = $bankAccount->account->currency_code ?? CurrencyAccessor::getDefaultCurrency();

                        // Hide if currencies are the same
                        return $invoiceCurrency === $bankCurrency;
                    }),
                Select::make('payment_method')
                    ->label('Payment method')
                    ->required()
                    ->options(PaymentMethod::class),
                Textarea::make('notes')
                    ->label('Notes'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('posted_at')
                    ->label('Date')
                    ->sortable()
                    ->defaultDateFormat(),
                TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('bankAccount.account.name')
                    ->label('Account')
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->weight(static fn (Transaction $transaction) => $transaction->reviewed ? null : FontWeight::SemiBold)
                    ->color(
                        static fn (Transaction $transaction) => match ($transaction->type) {
                            TransactionType::Deposit => Color::generateV3Palette('rgb(' . Color::Green[700] . ')'),
                            TransactionType::Journal => 'primary',
                            default => null,
                        }
                    )
                    ->sortable()
                    ->currency(static fn (Transaction $transaction) => $transaction->bankAccount?->account->currency_code ?? CurrencyAccessor::getDefaultCurrency()),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(fn () => $this->getOwnerRecord()->status === InvoiceStatus::Overpaid ? 'Refund Overpayment' : 'Record Payment')
                    ->modalHeading(fn (CreateAction $action) => $action->getLabel())
                    ->slideOver()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->visible(function () {
                        return $this->getOwnerRecord()->canRecordPayment();
                    })
                    ->mountUsing(function (Schema $schema) {
                        $record = $this->getOwnerRecord();
                        $schema->fill([
                            'posted_at' => now(),
                            'amount' => abs($record->amount_due),
                        ]);
                    })
                    ->databaseTransaction()
                    ->successNotificationTitle('Payment recorded')
                    ->action(function (CreateAction $action, array $data) {
                        /** @var Invoice $record */
                        $record = $this->getOwnerRecord();

                        $record->recordPayment($data);

                        $action->success();

                        $this->dispatch('refresh');
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->after(fn () => $this->dispatch('refresh')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
