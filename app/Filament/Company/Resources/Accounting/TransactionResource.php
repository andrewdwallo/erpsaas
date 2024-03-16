<?php

namespace App\Filament\Company\Resources\Accounting;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\DateFormat;
use App\Filament\Company\Resources\Accounting\TransactionResource\Pages;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Setting\Localization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $modelLabel = 'Transaction';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('posted_at')
                    ->label('Date')
                    ->required()
                    ->displayFormat('Y-m-d'),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\Select::make('bank_account_id')
                    ->label('Account')
                    ->options(static fn () => static::getBankAccountOptions())
                    ->live()
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->live()
                    ->options([
                        'deposit' => 'Deposit',
                        'withdrawal' => 'Withdrawal',
                    ])
                    ->required()
                    ->afterStateUpdated(static fn (Forms\Set $set, $state) => $set('account_id', Pages\ManageTransaction::getUncategorizedAccountByType($state)?->id)),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->money(static fn (Forms\Get $get) => BankAccount::find($get('bank_account_id'))?->account?->currency_code ?? 'USD')
                    ->required(),
                Forms\Components\Select::make('account_id')
                    ->label('Category')
                    ->options(static fn (Forms\Get $get) => static::getAccountOptions($get('type')))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->autosize()
                    ->rows(10)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Date')
                    ->sortable()
                    ->formatStateUsing(static function ($state) {
                        $dateFormat = Localization::firstOrFail()->date_format->value ?? DateFormat::DEFAULT;

                        return Carbon::parse($state)->translatedFormat($dateFormat);
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->label('Description'),
                Tables\Columns\TextColumn::make('bankAccount.account.name')
                    ->label('Account')
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->sortable()
                    ->weight(static fn (Transaction $record) => $record->reviewed ? null : FontWeight::SemiBold)
                    ->color(static fn (Transaction $record) => $record->type === 'deposit' ? Color::rgb('rgb(' . Color::Green[700] . ')') : null)
                    ->currency(static fn (Transaction $record) => $record->bankAccount->account->currency_code ?? 'USD', true),
            ])
            ->recordClasses(static fn (Transaction $record) => $record->reviewed ? 'bg-primary-300/10' : null)
            ->defaultSort('posted_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('markAsReviewed')
                    ->label('Mark as Reviewed')
                    ->view('filament.company.components.tables.actions.mark-as-reviewed')
                    ->icon(static fn (Transaction $record) => $record->reviewed ? 'heroicon-s-check-circle' : 'heroicon-o-check-circle')
                    ->color(static fn (Transaction $record, Tables\Actions\Action $action) => match (static::determineTransactionState($record, $action)) {
                        'reviewed' => 'primary',
                        'unreviewed' => Color::rgb('rgb(' . Color::Gray[600] . ')'),
                        'uncategorized' => 'gray',
                    })
                    ->tooltip(static fn (Transaction $record, Tables\Actions\Action $action) => match (static::determineTransactionState($record, $action)) {
                        'reviewed' => 'Reviewed',
                        'unreviewed' => 'Mark as Reviewed',
                        'uncategorized' => 'Categorize first to mark as reviewed',
                    })
                    ->disabled(static fn (Transaction $record) => in_array($record->account->type, [AccountType::UncategorizedRevenue, AccountType::UncategorizedExpense], true))
                    ->action(fn (Transaction $record) => $record->update(['reviewed' => ! $record->reviewed])),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->modalWidth(MaxWidth::ThreeExtraLarge)
                        ->stickyModalHeader()
                        ->stickyModalFooter(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ReplicateAction::make()
                        ->excludeAttributes(['created_by', 'updated_by', 'created_at', 'updated_at'])
                        ->modal(false)
                        ->beforeReplicaSaved(static function (Transaction $replica) {
                            $replica->description = '(Copy of) ' . $replica->description;
                        }),
                ])
                    ->dropdownPlacement('bottom-start')
                    ->dropdownWidth('max-w-fit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function determineTransactionState(Transaction $record, Tables\Actions\Action $action): string
    {
        if ($record->reviewed) {
            return 'reviewed';
        }

        if ($record->reviewed === false && $action->isEnabled()) {
            return 'unreviewed';
        }

        return 'uncategorized';
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTransaction::route('/'),
        ];
    }

    public static function getBankAccountOptions(): array
    {
        $bankAccounts = BankAccount::with('account.subtype')->get();

        return $bankAccounts->groupBy('account.subtype.name')
            ->map(fn (Collection $bankAccounts) => $bankAccounts->pluck('account.name', 'id'))
            ->toArray();
    }

    public static function getAccountOptions(string $type)
    {
        $excludedCategory = match ($type) {
            'deposit' => AccountCategory::Expense,
            'withdrawal' => AccountCategory::Revenue,
        };

        $accounts = Account::whereNot('category', $excludedCategory)->get();

        return $accounts->groupBy(fn (Account $account) => $account->category->getLabel())
            ->map(fn (Collection $accounts, string $category) => $accounts->mapWithKeys(static fn (Account $account) => [$account->id => $account->name]))
            ->toArray();
    }
}
