<?php

namespace App\Filament\Company\Pages\Accounting;

use App\Concerns\HasJournalEntryActions;
use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\JournalEntryType;
use App\Enums\Accounting\TransactionType;
use App\Facades\Accounting;
use App\Filament\Company\Pages\Service\ConnectedAccount;
use App\Filament\Forms\Components\DateRangeSelect;
use App\Filament\Forms\Components\JournalEntryRepeater;
use App\Filament\Tables\Actions\ReplicateBulkAction;
use App\Models\Accounting\Account;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Company;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Awcodes\TableRepeater\Header;
use Exception;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property Form $form
 */
class Transactions extends Page implements HasTable
{
    use HasJournalEntryActions;
    use InteractsWithTable;

    protected static string $view = 'filament.company.pages.accounting.transactions';

    protected static ?string $model = Transaction::class;

    protected static ?string $navigationGroup = 'Accounting';

    public string $fiscalYearStartDate = '';

    public string $fiscalYearEndDate = '';

    public function mount(): void
    {
        /** @var Company $company */
        $company = Filament::getTenant();
        $this->fiscalYearStartDate = $company->locale->fiscalYearStartDate();
        $this->fiscalYearEndDate = $company->locale->fiscalYearEndDate();
    }

    public static function getModel(): string
    {
        return static::$model;
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query();
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->buildTransactionAction('addIncome', 'Add income', TransactionType::Deposit),
            $this->buildTransactionAction('addExpense', 'Add expense', TransactionType::Withdrawal),
            Actions\CreateAction::make('addTransfer')
                ->label('Add transfer')
                ->modalHeading('Add Transfer')
                ->modalWidth(MaxWidth::ThreeExtraLarge)
                ->model(static::getModel())
                ->fillForm(fn (): array => $this->getFormDefaultsForType(TransactionType::Transfer))
                ->form(fn (Form $form) => $this->transferForm($form))
                ->button()
                ->outlined(),
            Actions\ActionGroup::make([
                Actions\CreateAction::make('addJournalTransaction')
                    ->label('Add journal transaction')
                    ->fillForm(fn (): array => $this->getFormDefaultsForType(TransactionType::Journal))
                    ->modalWidth(MaxWidth::Screen)
                    ->model(static::getModel())
                    ->form(fn (Form $form) => $this->journalTransactionForm($form))
                    ->modalSubmitAction(fn (Actions\StaticAction $action) => $action->disabled(! $this->isJournalEntryBalanced()))
                    ->groupedIcon(null)
                    ->modalHeading('Journal Entry')
                    ->mutateFormDataUsing(static fn (array $data) => array_merge($data, ['type' => TransactionType::Journal]))
                    ->afterFormFilled(fn () => $this->resetJournalEntryAmounts())
                    ->after(fn (Transaction $transaction) => $transaction->updateAmountIfBalanced()),
                Actions\Action::make('connectBank')
                    ->label('Connect your bank')
                    ->url(ConnectedAccount::getUrl()),
            ])
                ->label('More')
                ->button()
                ->outlined()
                ->dropdownWidth('max-w-fit')
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-c-chevron-down')
                ->iconSize(IconSize::Small)
                ->iconPosition(IconPosition::After),
        ];
    }

    public function transferForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('posted_at')
                    ->label('Date')
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\Select::make('bank_account_id')
                    ->label('From account')
                    ->options(fn (Get $get, ?Transaction $transaction) => $this->getBankAccountOptions(excludedAccountId: $get('account_id'), currentBankAccountId: $transaction?->bank_account_id))
                    ->live()
                    ->searchable()
                    ->afterStateUpdated(function (Set $set, $state, $old, Get $get) {
                        $amount = CurrencyConverter::convertAndSet(
                            BankAccount::find($state)->account->currency_code,
                            BankAccount::find($old)->account->currency_code ?? CurrencyAccessor::getDefaultCurrency(),
                            $get('amount')
                        );

                        if ($amount !== null) {
                            $set('amount', $amount);
                        }
                    })
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        TransactionType::Transfer->value => TransactionType::Transfer->getLabel(),
                    ])
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->money(static fn (Forms\Get $get) => BankAccount::find($get('bank_account_id'))?->account?->currency_code ?? CurrencyAccessor::getDefaultCurrency())
                    ->required(),
                Forms\Components\Select::make('account_id')
                    ->label('To account')
                    ->live()
                    ->options(fn (Get $get, ?Transaction $transaction) => $this->getBankAccountAccountOptions(excludedBankAccountId: $get('bank_account_id'), currentAccountId: $transaction?->account_id))
                    ->searchable()
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->autosize()
                    ->rows(10)
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public function transactionForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('posted_at')
                    ->label('Date')
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\Select::make('bank_account_id')
                    ->label('Account')
                    ->options(fn (?Transaction $transaction) => $this->getBankAccountOptions(currentBankAccountId: $transaction?->bank_account_id))
                    ->live()
                    ->searchable()
                    ->afterStateUpdated(function (Set $set, $state, $old, Get $get) {
                        $amount = CurrencyConverter::convertAndSet(
                            BankAccount::find($state)->account->currency_code,
                            BankAccount::find($old)->account->currency_code ?? CurrencyAccessor::getDefaultCurrency(),
                            $get('amount')
                        );

                        if ($amount !== null) {
                            $set('amount', $amount);
                        }
                    })
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->live()
                    ->options([
                        TransactionType::Deposit->value => TransactionType::Deposit->getLabel(),
                        TransactionType::Withdrawal->value => TransactionType::Withdrawal->getLabel(),
                    ])
                    ->required()
                    ->afterStateUpdated(static fn (Forms\Set $set, $state) => $set('account_id', static::getUncategorizedAccountByType(TransactionType::parse($state))?->id)),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->money(static fn (Forms\Get $get) => BankAccount::find($get('bank_account_id'))?->account?->currency_code ?? CurrencyAccessor::getDefaultCurrency())
                    ->required(),
                Forms\Components\Select::make('account_id')
                    ->label('Category')
                    ->options(fn (Forms\Get $get, ?Transaction $transaction) => $this->getChartAccountOptions(type: TransactionType::parse($get('type')), nominalAccountsOnly: true, currentAccountId: $transaction?->account_id))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->autosize()
                    ->rows(10)
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public function journalTransactionForm(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->contained(false)
                    ->tabs([
                        $this->getJournalTransactionFormEditTab(),
                        $this->getJournalTransactionFormNotesTab(),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery())
            ->modifyQueryUsing(function (Builder $query) {
                $query->with([
                    'account',
                    'bankAccount.account',
                    'journalEntries.account',
                ])
                    ->where(function (Builder $query) {
                        $query->whereNull('transactionable_id')
                            ->orWhere('is_payment', true);
                    });
            })
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
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bankAccount.account.name')
                    ->label('Account')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Category')
                    ->prefix(static fn (Transaction $transaction) => $transaction->type->isTransfer() ? 'Transfer to ' : null)
                    ->toggleable()
                    ->state(static fn (Transaction $transaction) => $transaction->account->name ?? 'Journal Entry'),
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
                    ->currency(static fn (Transaction $transaction) => $transaction->bankAccount?->account->currency_code),
            ])
            ->recordClasses(static fn (Transaction $transaction) => $transaction->reviewed ? 'bg-primary-300/10' : null)
            ->defaultSort('posted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label('Account')
                    ->searchable()
                    ->options(fn () => $this->getBankAccountOptions(false)),
                Tables\Filters\SelectFilter::make('account_id')
                    ->label('Category')
                    ->multiple()
                    ->options(fn () => $this->getChartAccountOptions(nominalAccountsOnly: false)),
                Tables\Filters\TernaryFilter::make('reviewed')
                    ->label('Status')
                    ->native(false)
                    ->trueLabel('Reviewed')
                    ->falseLabel('Not Reviewed'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->native(false)
                    ->options(TransactionType::class),
                $this->buildDateRangeFilter('posted_at', 'Posted', true),
                $this->buildDateRangeFilter('updated_at', 'Last modified'),
            ])
            ->filtersFormSchema(fn (array $filters): array => [
                Grid::make()
                    ->schema([
                        $filters['bank_account_id'],
                        $filters['account_id'],
                        $filters['reviewed'],
                        $filters['type'],
                    ])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'border-b border-gray-200 dark:border-white/10 pb-8']),
                $filters['posted_at'],
                $filters['updated_at'],
            ])
            ->deferFilters()
            ->deferLoading()
            ->filtersFormWidth(MaxWidth::ThreeExtraLarge)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->slideOver()
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalCancelAction(false)
                    ->extraModalFooterActions(function (Table $table) use ($action) {
                        return [
                            $table->getFiltersApplyAction()
                                ->close(),
                            Actions\StaticAction::make('cancel')
                                ->label($action->getModalCancelActionLabel())
                                ->button()
                                ->close()
                                ->color('gray'),
                            Tables\Actions\Action::make('resetFilters')
                                ->label(__('Clear all'))
                                ->color('primary')
                                ->link()
                                ->extraAttributes([
                                    'class' => 'me-auto',
                                ])
                                ->action('resetTableFiltersForm'),
                        ];
                    })
            )
            ->actions([
                Tables\Actions\Action::make('markAsReviewed')
                    ->label('Mark as reviewed')
                    ->view('filament.company.components.tables.actions.mark-as-reviewed')
                    ->icon(static fn (Transaction $transaction) => $transaction->reviewed ? 'heroicon-s-check-circle' : 'heroicon-o-check-circle')
                    ->color(static fn (Transaction $transaction, Tables\Actions\Action $action) => match (static::determineTransactionState($transaction, $action)) {
                        'reviewed' => 'primary',
                        'unreviewed' => Color::rgb('rgb(' . Color::Gray[600] . ')'),
                        'uncategorized' => 'gray',
                    })
                    ->tooltip(static fn (Transaction $transaction, Tables\Actions\Action $action) => match (static::determineTransactionState($transaction, $action)) {
                        'reviewed' => 'Reviewed',
                        'unreviewed' => 'Mark as reviewed',
                        'uncategorized' => 'Categorize first to mark as reviewed',
                    })
                    ->disabled(fn (Transaction $transaction): bool => $transaction->isUncategorized())
                    ->action(fn (Transaction $transaction) => $transaction->update(['reviewed' => ! $transaction->reviewed])),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\EditAction::make('editTransaction')
                            ->label('Edit transaction')
                            ->modalHeading('Edit Transaction')
                            ->modalWidth(MaxWidth::ThreeExtraLarge)
                            ->form(fn (Form $form) => $this->transactionForm($form))
                            ->visible(static fn (Transaction $transaction) => $transaction->type->isStandard()),
                        Tables\Actions\EditAction::make('editTransfer')
                            ->label('Edit transfer')
                            ->modalHeading('Edit Transfer')
                            ->modalWidth(MaxWidth::ThreeExtraLarge)
                            ->form(fn (Form $form) => $this->transferForm($form))
                            ->visible(static fn (Transaction $transaction) => $transaction->type->isTransfer()),
                        Tables\Actions\EditAction::make('editJournalTransaction')
                            ->label('Edit journal transaction')
                            ->modalHeading('Journal Entry')
                            ->modalWidth(MaxWidth::Screen)
                            ->form(fn (Form $form) => $this->journalTransactionForm($form))
                            ->afterFormFilled(function (Transaction $transaction) {
                                $debitAmounts = $transaction->journalEntries->sumDebits()->getAmount();
                                $creditAmounts = $transaction->journalEntries->sumCredits()->getAmount();

                                $this->setDebitAmount($debitAmounts);
                                $this->setCreditAmount($creditAmounts);
                            })
                            ->modalSubmitAction(fn (Actions\StaticAction $action) => $action->disabled(! $this->isJournalEntryBalanced()))
                            ->after(fn (Transaction $transaction) => $transaction->updateAmountIfBalanced())
                            ->visible(static fn (Transaction $transaction) => $transaction->type->isJournal()),
                        Tables\Actions\ReplicateAction::make()
                            ->excludeAttributes(['created_by', 'updated_by', 'created_at', 'updated_at'])
                            ->modal(false)
                            ->beforeReplicaSaved(static function (Transaction $replica) {
                                $replica->description = '(Copy of) ' . $replica->description;
                            })
                            ->after(static function (Transaction $original, Transaction $replica) {
                                $original->journalEntries->each(function (JournalEntry $entry) use ($replica) {
                                    $entry->replicate([
                                        'transaction_id',
                                    ])->fill([
                                        'transaction_id' => $replica->id,
                                    ])->save();
                                });
                            }),
                    ])->dropdown(false),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ReplicateBulkAction::make()
                        ->label('Replicate')
                        ->modalWidth(MaxWidth::Large)
                        ->modalDescription('Replicating transactions will also replicate their journal entries. Are you sure you want to proceed?')
                        ->successNotificationTitle('Transactions replicated successfully')
                        ->failureNotificationTitle('Failed to replicate transactions')
                        ->deselectRecordsAfterCompletion()
                        ->excludeAttributes(['created_by', 'updated_by', 'created_at', 'updated_at'])
                        ->beforeReplicaSaved(static function (Transaction $replica) {
                            $replica->description = '(Copy of) ' . $replica->description;
                        })
                        ->withReplicatedRelationships(['journalEntries']),
                ]),
            ]);
    }

    protected function buildTransactionAction(string $name, string $label, TransactionType $type): Actions\CreateAction
    {
        return Actions\CreateAction::make($name)
            ->label($label)
            ->modalWidth(MaxWidth::ThreeExtraLarge)
            ->model(static::getModel())
            ->fillForm(fn (): array => $this->getFormDefaultsForType($type))
            ->form(fn (Form $form) => $this->transactionForm($form))
            ->button()
            ->outlined();
    }

    protected function getFormDefaultsForType(TransactionType $type): array
    {
        $commonDefaults = [
            'posted_at' => today(),
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
            'account_id' => static::getUncategorizedAccountByType($journalEntryType->isDebit() ? TransactionType::Withdrawal : TransactionType::Deposit)?->id,
            'amount' => '0.00',
        ];
    }

    protected function transactionDefaults(TransactionType $type): array
    {
        return [
            'type' => $type,
            'bank_account_id' => BankAccount::where('enabled', true)->first()?->id,
            'amount' => '0.00',
            'account_id' => ! $type->isTransfer() ? static::getUncategorizedAccountByType($type)->id : null,
        ];
    }

    public static function getUncategorizedAccountByType(TransactionType $type): ?Account
    {
        [$category, $accountName] = match ($type) {
            TransactionType::Deposit => [AccountCategory::Revenue, 'Uncategorized Income'],
            TransactionType::Withdrawal => [AccountCategory::Expense, 'Uncategorized Expense'],
            default => [null, null],
        };

        return Account::where('category', $category)
            ->where('name', $accountName)
            ->first();
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
        return Grid::make(8)
            ->schema([
                DatePicker::make('posted_at')
                    ->label('Date')
                    ->softRequired()
                    ->displayFormat('Y-m-d'),
                TextInput::make('description')
                    ->label('Description')
                    ->columnSpan(2),
            ]);
    }

    protected function getJournalEntriesTableRepeater(): JournalEntryRepeater
    {
        return JournalEntryRepeater::make('journalEntries')
            ->relationship('journalEntries')
            ->hiddenLabel()
            ->columns(4)
            ->headers($this->getJournalEntriesTableRepeaterHeaders())
            ->schema($this->getJournalEntriesTableRepeaterSchema())
            ->streamlined()
            ->deletable(fn (JournalEntryRepeater $repeater) => $repeater->getItemsCount() > 2)
            ->deleteAction(function (Forms\Components\Actions\Action $action) {
                return $action
                    ->action(function (array $arguments, JournalEntryRepeater $component): void {
                        $items = $component->getState();

                        $amount = $items[$arguments['item']]['amount'];
                        $type = $items[$arguments['item']]['type'];

                        $this->updateJournalEntryAmount(JournalEntryType::parse($type), '0.00', $amount);

                        unset($items[$arguments['item']]);

                        $component->state($items);

                        $component->callAfterStateUpdated();
                    });
            })
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
            Header::make('type')
                ->width('150px')
                ->label('Type'),
            Header::make('description')
                ->width('320px')
                ->label('Description'),
            Header::make('account_id')
                ->width('320px')
                ->label('Account'),
            Header::make('amount')
                ->width('192px')
                ->label('Amount'),
        ];
    }

    protected function getJournalEntriesTableRepeaterSchema(): array
    {
        return [
            Select::make('type')
                ->label('Type')
                ->options(JournalEntryType::class)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                    $this->adjustJournalEntryAmountsForTypeChange(JournalEntryType::parse($state), JournalEntryType::parse($old), $get('amount'));
                })
                ->softRequired(),
            TextInput::make('description')
                ->label('Description'),
            Select::make('account_id')
                ->label('Account')
                ->options(fn (?JournalEntry $journalEntry): array => $this->getChartAccountOptions(currentAccountId: $journalEntry?->account_id))
                ->live()
                ->softRequired()
                ->searchable(),
            TextInput::make('amount')
                ->label('Amount')
                ->live()
                ->mask(moneyMask(CurrencyAccessor::getDefaultCurrency()))
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                    $this->updateJournalEntryAmount(JournalEntryType::parse($get('type')), $state, $old);
                })
                ->softRequired(),
        ];
    }

    protected function buildAddJournalEntryAction(JournalEntryType $type): FormAction
    {
        $typeLabel = $type->getLabel();

        return FormAction::make("add{$typeLabel}Entry")
            ->label("Add {$typeLabel} entry")
            ->button()
            ->outlined()
            ->color($type->isDebit() ? 'primary' : 'gray')
            ->iconSize(IconSize::Small)
            ->iconPosition(IconPosition::Before)
            ->action(function (JournalEntryRepeater $component) use ($type) {
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

    /**
     * @throws Exception
     */
    protected function buildDateRangeFilter(string $fieldPrefix, string $label, bool $hasBottomBorder = false): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make($fieldPrefix)
            ->columnSpanFull()
            ->form([
                Grid::make()
                    ->live()
                    ->schema([
                        DateRangeSelect::make("{$fieldPrefix}_date_range")
                            ->label($label)
                            ->selectablePlaceholder(false)
                            ->placeholder('Select a date range')
                            ->startDateField("{$fieldPrefix}_start_date")
                            ->endDateField("{$fieldPrefix}_end_date"),
                        DatePicker::make("{$fieldPrefix}_start_date")
                            ->label("{$label} from")
                            ->columnStart(1)
                            ->afterStateUpdated(static function (Set $set) use ($fieldPrefix) {
                                $set("{$fieldPrefix}_date_range", 'Custom');
                            }),
                        DatePicker::make("{$fieldPrefix}_end_date")
                            ->label("{$label} to")
                            ->afterStateUpdated(static function (Set $set) use ($fieldPrefix) {
                                $set("{$fieldPrefix}_date_range", 'Custom');
                            }),
                    ])
                    ->extraAttributes($hasBottomBorder ? ['class' => 'border-b border-gray-200 dark:border-white/10 pb-8'] : []),
            ])
            ->query(function (Builder $query, array $data) use ($fieldPrefix): Builder {
                $query
                    ->when($data["{$fieldPrefix}_start_date"], fn (Builder $query, $startDate) => $query->whereDate($fieldPrefix, '>=', $startDate))
                    ->when($data["{$fieldPrefix}_end_date"], fn (Builder $query, $endDate) => $query->whereDate($fieldPrefix, '<=', $endDate));

                return $query;
            })
            ->indicateUsing(function (array $data) use ($fieldPrefix, $label): array {
                $indicators = [];

                $this->addIndicatorForDateRange($data, "{$fieldPrefix}_start_date", "{$fieldPrefix}_end_date", $label, $indicators);

                return $indicators;
            });

    }

    protected function addIndicatorForSingleSelection($data, $key, $label, &$indicators): void
    {
        if (filled($data[$key])) {
            $indicators[] = Tables\Filters\Indicator::make($label)
                ->removeField($key);
        }
    }

    protected function addMultipleSelectionIndicator($data, $key, callable $labelRetriever, $field, &$indicators): void
    {
        if (filled($data[$key])) {
            $labels = collect($data[$key])->map($labelRetriever);
            $additionalCount = $labels->count() - 1;
            $indicatorLabel = $additionalCount > 0 ? "{$labels->first()} + {$additionalCount}" : $labels->first();
            $indicators[] = Tables\Filters\Indicator::make($indicatorLabel)
                ->removeField($field);
        }
    }

    protected function addIndicatorForDateRange($data, $startKey, $endKey, $labelPrefix, &$indicators): void
    {
        $formattedStartDate = filled($data[$startKey]) ? Carbon::parse($data[$startKey])->toFormattedDateString() : null;
        $formattedEndDate = filled($data[$endKey]) ? Carbon::parse($data[$endKey])->toFormattedDateString() : null;
        if ($formattedStartDate && $formattedEndDate) {
            // If both start and end dates are set, show the combined date range as the indicator, no specific field needs to be removed since the entire filter will be removed
            $indicators[] = Tables\Filters\Indicator::make("{$labelPrefix}: {$formattedStartDate} - {$formattedEndDate}");
        } else {
            if ($formattedStartDate) {
                $indicators[] = Tables\Filters\Indicator::make("{$labelPrefix} After: {$formattedStartDate}")
                    ->removeField($startKey);
            }

            if ($formattedEndDate) {
                $indicators[] = Tables\Filters\Indicator::make("{$labelPrefix} Before: {$formattedEndDate}")
                    ->removeField($endKey);
            }
        }
    }

    protected static function determineTransactionState(Transaction $transaction, Tables\Actions\Action $action): string
    {
        if ($transaction->reviewed) {
            return 'reviewed';
        }

        if ($transaction->reviewed === false && $action->isEnabled()) {
            return 'unreviewed';
        }

        return 'uncategorized';
    }

    protected function getBankAccountOptions(?int $excludedAccountId = null, ?int $currentBankAccountId = null): array
    {
        return BankAccount::query()
            ->whereHas('account', function (Builder $query) {
                $query->where('archived', false);
            })
            ->with(['account' => function ($query) {
                $query->where('archived', false);
            }, 'account.subtype' => function ($query) {
                $query->select(['id', 'name']);
            }])
            ->when($excludedAccountId, fn (Builder $query) => $query->where('account_id', '!=', $excludedAccountId))
            ->when($currentBankAccountId, fn (Builder $query) => $query->orWhere('id', $currentBankAccountId))
            ->get()
            ->groupBy('account.subtype.name')
            ->map(fn (Collection $bankAccounts, string $subtype) => $bankAccounts->pluck('account.name', 'id'))
            ->toArray();
    }

    protected function getBankAccountAccountOptions(?int $excludedBankAccountId = null, ?int $currentAccountId = null): array
    {
        return Account::query()
            ->whereHas('bankAccount', function (Builder $query) use ($excludedBankAccountId) {
                // Exclude the specific bank account if provided
                if ($excludedBankAccountId) {
                    $query->whereNot('id', $excludedBankAccountId);
                }
            })
            ->where(function (Builder $query) use ($currentAccountId) {
                $query->where('archived', false)
                    ->orWhere('id', $currentAccountId);
            })
            ->get()
            ->groupBy(fn (Account $account) => $account->category->getPluralLabel())
            ->map(fn (Collection $accounts, string $category) => $accounts->pluck('name', 'id'))
            ->toArray();
    }

    protected function getChartAccountOptions(?TransactionType $type = null, ?bool $nominalAccountsOnly = null, ?int $currentAccountId = null): array
    {
        $nominalAccountsOnly ??= false;

        $excludedCategory = match ($type) {
            TransactionType::Deposit => AccountCategory::Expense,
            TransactionType::Withdrawal => AccountCategory::Revenue,
            default => null,
        };

        return Account::query()
            ->when($nominalAccountsOnly, fn (Builder $query) => $query->doesntHave('bankAccount'))
            ->when($excludedCategory, fn (Builder $query) => $query->whereNot('category', $excludedCategory))
            ->where(function (Builder $query) use ($currentAccountId) {
                $query->where('archived', false)
                    ->orWhere('id', $currentAccountId);
            })
            ->get()
            ->groupBy(fn (Account $account) => $account->category->getPluralLabel())
            ->map(fn (Collection $accounts, string $category) => $accounts->pluck('name', 'id'))
            ->toArray();
    }

    protected function getBalanceForAllAccounts(): string
    {
        return Accounting::getTotalBalanceForAllBankAccounts($this->fiscalYearStartDate, $this->fiscalYearEndDate)->format();
    }
}
