<?php

namespace App\Concerns;

use App\Enums\Accounting\JournalEntryType;
use App\Enums\Accounting\TransactionType;
use App\Filament\Forms\Components\CustomTableRepeater;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

trait HasTransactionAction
{
    use HasJournalEntryActions;

    protected TransactionType | Closure | null $transactionType = null;

    public function type(TransactionType | Closure | null $type = null): static
    {
        $this->transactionType = $type;

        return $this;
    }

    public function getTransactionType(): ?TransactionType
    {
        return $this->evaluate($this->transactionType);
    }

    protected function getFormDefaultsForType(TransactionType $type): array
    {
        $commonDefaults = [
            'posted_at' => company_today()->toDateString(),
        ];

        return match ($type) {
            TransactionType::Deposit, TransactionType::Withdrawal, TransactionType::Transfer => array_merge($commonDefaults, $this->transactionDefaults($type)),
            TransactionType::Journal => array_merge($commonDefaults, $this->journalEntryDefaults()),
        };
    }

    protected function journalEntryDefaults(): array
    {
        return [
            'journalEntries' => [
                $this->defaultEntry(JournalEntryType::Debit),
                $this->defaultEntry(JournalEntryType::Credit),
            ],
        ];
    }

    protected function defaultEntry(JournalEntryType $journalEntryType): array
    {
        return [
            'type' => $journalEntryType,
            'account_id' => Transaction::getUncategorizedAccountByType($journalEntryType->isDebit() ? TransactionType::Withdrawal : TransactionType::Deposit)?->id,
            'amount' => '0.00',
        ];
    }

    protected function transactionDefaults(TransactionType $type): array
    {
        return [
            'type' => $type,
            'bank_account_id' => BankAccount::where('enabled', true)->first()?->id,
            'amount' => '0.00',
            'account_id' => ! $type->isTransfer() ? Transaction::getUncategorizedAccountByType($type)->id : null,
        ];
    }

    public function transactionForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('posted_at')
                    ->label('Date')
                    ->required(),
                TextInput::make('description')
                    ->label('Description'),
                Select::make('bank_account_id')
                    ->label('Account')
                    ->options(fn (?Transaction $transaction) => Transaction::getBankAccountOptions(currentBankAccountId: $transaction?->bank_account_id))
                    ->live()
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->label('Type')
                    ->live()
                    ->options([
                        TransactionType::Deposit->value => TransactionType::Deposit->getLabel(),
                        TransactionType::Withdrawal->value => TransactionType::Withdrawal->getLabel(),
                    ])
                    ->required()
                    ->afterStateUpdated(static fn (Set $set, $state) => $set('account_id', Transaction::getUncategorizedAccountByType(TransactionType::parse($state))?->id)),
                TextInput::make('amount')
                    ->label('Amount')
                    ->money(static fn (Get $get) => BankAccount::find($get('bank_account_id'))?->account?->currency_code ?? CurrencyAccessor::getDefaultCurrency())
                    ->required(),
                Select::make('account_id')
                    ->label('Category')
                    ->options(fn (Get $get, ?Transaction $transaction) => Transaction::getTransactionAccountOptions(type: TransactionType::parse($get('type')), currentAccountId: $transaction?->account_id))
                    ->searchable()
                    ->required(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->autosize()
                    ->rows(10)
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public function transferForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('posted_at')
                    ->label('Date')
                    ->required(),
                TextInput::make('description')
                    ->label('Description'),
                Select::make('bank_account_id')
                    ->label('From account')
                    ->options(fn (Get $get, ?Transaction $transaction) => Transaction::getBankAccountOptions(excludedAccountId: $get('account_id'), currentBankAccountId: $transaction?->bank_account_id))
                    ->live()
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->label('Type')
                    ->options([
                        TransactionType::Transfer->value => TransactionType::Transfer->getLabel(),
                    ])
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                TextInput::make('amount')
                    ->label('Amount')
                    ->money(static fn (Get $get) => BankAccount::find($get('bank_account_id'))?->account?->currency_code ?? CurrencyAccessor::getDefaultCurrency())
                    ->required(),
                Select::make('account_id')
                    ->label('To account')
                    ->live()
                    ->options(fn (Get $get, ?Transaction $transaction) => Transaction::getBankAccountAccountOptions(excludedBankAccountId: $get('bank_account_id'), currentAccountId: $transaction?->account_id))
                    ->searchable()
                    ->required(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->autosize()
                    ->rows(10)
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public function journalTransactionForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->contained(false)
                    ->tabs([
                        $this->getJournalTransactionFormEditTab(),
                        $this->getJournalTransactionFormNotesTab(),
                    ]),
            ])
            ->columns(1);
    }

    protected function getJournalTransactionFormEditTab(): Tab
    {
        return Tab::make('Edit')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->schema([
                $this->getTransactionDetailsGrid(),
                $this->getJournalEntriesTableRepeater(),
            ]);
    }

    protected function getJournalTransactionFormNotesTab(): Tab
    {
        return Tab::make('Notes')
            ->label('Notes')
            ->icon('heroicon-o-clipboard')
            ->id('notes')
            ->schema([
                $this->getTransactionDetailsGrid(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(10)
                    ->autosize(),
            ]);
    }

    protected function getTransactionDetailsGrid(): Grid
    {
        return Grid::make(6)
            ->schema([
                DatePicker::make('posted_at')
                    ->label('Date')
                    ->softRequired(),
                TextInput::make('description')
                    ->label('Description')
                    ->columnSpan(2),
            ]);
    }

    protected function getJournalEntriesTableRepeater(): Repeater
    {
        return CustomTableRepeater::make('journalEntries')
            ->relationship('journalEntries')
            ->hiddenLabel()
            ->table($this->getJournalEntriesTableRepeaterHeaders())
            ->schema($this->getJournalEntriesTableRepeaterSchema())
            ->minItems(2)
            ->deleteAction(function (Action $action) {
                return $action
                    ->action(function (array $arguments, Repeater $component): void {
                        $items = $component->getState();

                        $amount = $items[$arguments['item']]['amount'];
                        $type = $items[$arguments['item']]['type'];

                        $this->updateJournalEntryAmount(JournalEntryType::parse($type), '0.00', $amount);

                        unset($items[$arguments['item']]);

                        $component->state($items);

                        $component->callAfterStateUpdated();
                    });
            })
            ->rules([
                function () {
                    return function (string $attribute, $value, Closure $fail) {
                        if (empty($value) || ! is_array($value)) {
                            $fail('Journal entries are required.');

                            return;
                        }

                        $hasDebit = false;
                        $hasCredit = false;
                        $totalDebits = 0;
                        $totalCredits = 0;

                        foreach ($value as $entry) {
                            if (! isset($entry['type']) || ! isset($entry['amount'])) {
                                continue;
                            }

                            $entryType = JournalEntryType::parse($entry['type']);
                            $amount = CurrencyConverter::convertToCents($entry['amount'], 'USD');

                            if ($entryType->isDebit()) {
                                $hasDebit = true;
                                $totalDebits += $amount;
                            } elseif ($entryType->isCredit()) {
                                $hasCredit = true;
                                $totalCredits += $amount;
                            }
                        }

                        if (! $hasDebit) {
                            $fail('At least one debit entry is required.');
                        }

                        if (! $hasCredit) {
                            $fail('At least one credit entry is required.');
                        }

                        if ($totalDebits !== $totalCredits) {
                            $debitFormatted = CurrencyConverter::formatCentsToMoney($totalDebits, CurrencyAccessor::getDefaultCurrency());
                            $creditFormatted = CurrencyConverter::formatCentsToMoney($totalCredits, CurrencyAccessor::getDefaultCurrency());
                            $fail("Total debits ({$debitFormatted}) must equal total credits ({$creditFormatted}).");
                        }
                    };
                },
            ])
            ->minItems(2)
            ->defaultItems(2)
            ->addable(false)
            ->footerItem(fn (): View => $this->getJournalTransactionModalFooter())
            ->extraActions([
                $this->buildAddJournalEntryAction(JournalEntryType::Debit),
                $this->buildAddJournalEntryAction(JournalEntryType::Credit),
            ]);
    }

    protected function getJournalEntriesTableRepeaterHeaders(): array
    {
        return [
            TableColumn::make('Type')
                ->width('150px'),
            TableColumn::make('Description')
                ->width('320px'),
            TableColumn::make('Account')
                ->width('320px'),
            TableColumn::make('Amount')
                ->width('192px')
                ->alignEnd(),
        ];
    }

    protected function getJournalEntriesTableRepeaterSchema(): array
    {
        return [
            Select::make('type')
                ->label('Type')
                ->options(JournalEntryType::class)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, $state, $old) {
                    $this->adjustJournalEntryAmountsForTypeChange(JournalEntryType::parse($state), JournalEntryType::parse($old), $get('amount'));
                })
                ->softRequired(),
            TextInput::make('description')
                ->label('Description'),
            Select::make('account_id')
                ->label('Account')
                ->options(fn (?JournalEntry $journalEntry): array => Transaction::getJournalAccountOptions(currentAccountId: $journalEntry?->account_id))
                ->softRequired()
                ->searchable(),
            TextInput::make('amount')
                ->label('Amount')
                ->live(onBlur: true)
                ->money()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                    $this->updateJournalEntryAmount(JournalEntryType::parse($get('type')), $state, $old);
                })
                ->softRequired(),
        ];
    }

    protected function buildAddJournalEntryAction(JournalEntryType $type): Action
    {
        $typeLabel = $type->getLabel();

        return Action::make("add{$typeLabel}Entry")
            ->button()
            ->outlined()
            ->color($type->isDebit() ? 'primary' : 'gray')
            ->action(function (CustomTableRepeater $component) use ($type) {
                $state = $component->getState();
                $newUuid = (string) Str::uuid();
                $state[$newUuid] = $this->defaultEntry($type);

                $component->state($state);
            });
    }

    public function getJournalTransactionModalFooter(): View
    {
        return view(
            'filament.company.components.actions.journal-entry-footer',
            [
                'debitAmount' => $this->getFormattedDebitAmount(),
                'creditAmount' => $this->getFormattedCreditAmount(),
                'difference' => $this->getFormattedBalanceDifference(),
                'isJournalBalanced' => $this->isJournalEntryBalanced(),
            ],
        );
    }
}
