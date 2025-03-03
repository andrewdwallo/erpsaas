<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\RelationManagers;

use App\Enums\Accounting\InvoiceStatus;
use App\Enums\Accounting\PaymentMethod;
use App\Enums\Accounting\TransactionType;
use App\Filament\Company\Resources\Sales\InvoiceResource\Pages\ViewInvoice;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
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
        return $ownerRecord->status !== InvoiceStatus::Draft && $pageClass === ViewInvoice::class;
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\DatePicker::make('posted_at')
                    ->label('Date'),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->required()
                    ->money()
                    ->live(onBlur: true)
                    ->helperText(function (RelationManager $livewire, $state, ?Transaction $record) {
                        if (! CurrencyConverter::isValidAmount($state)) {
                            return null;
                        }

                        /** @var Invoice $ownerRecord */
                        $ownerRecord = $livewire->getOwnerRecord();

                        $amountDue = $ownerRecord->getRawOriginal('amount_due');

                        $amount = CurrencyConverter::convertToCents($state);

                        if ($amount <= 0) {
                            return 'Please enter a valid positive amount';
                        }

                        $currentPaymentAmount = $record?->getRawOriginal('amount') ?? 0;

                        if ($ownerRecord->status === InvoiceStatus::Overpaid) {
                            $newAmountDue = $amountDue + $amount - $currentPaymentAmount;
                        } else {
                            $newAmountDue = $amountDue - $amount + $currentPaymentAmount;
                        }

                        return match (true) {
                            $newAmountDue > 0 => 'Amount due after payment will be ' . CurrencyConverter::formatCentsToMoney($newAmountDue),
                            $newAmountDue === 0 => 'Invoice will be fully paid',
                            default => 'Invoice will be overpaid by ' . CurrencyConverter::formatCentsToMoney(abs($newAmountDue)),
                        };
                    })
                    ->rules([
                        static fn (): Closure => static function (string $attribute, $value, Closure $fail) {
                            if (! CurrencyConverter::isValidAmount($value)) {
                                $fail('Please enter a valid amount');
                            }
                        },
                    ]),
                Forms\Components\Select::make('payment_method')
                    ->label('Payment method')
                    ->required()
                    ->options(PaymentMethod::class),
                Forms\Components\Select::make('bank_account_id')
                    ->label('Account')
                    ->required()
                    ->options(BankAccount::query()
                        ->get()
                        ->pluck('account.name', 'id'))
                    ->searchable(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Date')
                    ->sortable()
                    ->defaultDateFormat(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bankAccount.account.name')
                    ->label('Account')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->weight(static fn (Transaction $transaction) => $transaction->reviewed ? null : FontWeight::SemiBold)
                    ->color(
                        static fn (Transaction $transaction) => match ($transaction->type) {
                            TransactionType::Deposit => Color::rgb('rgb(' . Color::Green[700] . ')'),
                            TransactionType::Journal => 'primary',
                            default => null,
                        }
                    )
                    ->sortable()
                    ->currency(static fn (Transaction $transaction) => $transaction->bankAccount?->account->currency_code ?? CurrencyAccessor::getDefaultCurrency(), true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(fn () => $this->getOwnerRecord()->status === InvoiceStatus::Overpaid ? 'Refund Overpayment' : 'Record Payment')
                    ->modalHeading(fn (Tables\Actions\CreateAction $action) => $action->getLabel())
                    ->modalWidth(MaxWidth::TwoExtraLarge)
                    ->visible(function () {
                        return $this->getOwnerRecord()->canRecordPayment();
                    })
                    ->mountUsing(function (Form $form) {
                        $record = $this->getOwnerRecord();
                        $form->fill([
                            'posted_at' => now(),
                            'amount' => $record->status === InvoiceStatus::Overpaid ? ltrim($record->amount_due, '-') : $record->amount_due,
                        ]);
                    })
                    ->databaseTransaction()
                    ->successNotificationTitle('Payment recorded')
                    ->action(function (Tables\Actions\CreateAction $action, array $data) {
                        /** @var Invoice $record */
                        $record = $this->getOwnerRecord();

                        $record->recordPayment($data);

                        $action->success();

                        $this->dispatch('refresh');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth(MaxWidth::TwoExtraLarge)
                    ->after(fn () => $this->dispatch('refresh')),
                Tables\Actions\DeleteAction::make()
                    ->after(fn () => $this->dispatch('refresh')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
