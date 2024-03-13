<?php

namespace App\Filament\Company\Resources\Accounting;

use App\Enums\Accounting\AccountCategory;
use App\Enums\DateFormat;
use App\Filament\Company\Resources\Accounting\TransactionResource\Pages;
use App\Models\Accounting\Account;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Setting\Localization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('posted_at')
                    ->label('Date')
                    ->required()
                    ->displayFormat('Y-m-d')
                    ->default(now()->format('Y-m-d')),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\Select::make('bank_account_id')
                    ->label('Account')
                    ->options(fn () => static::getBankAccountOptions())
                    ->live()
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('method')
                    ->label('Type')
                    ->live()
                    ->options([
                        'deposit' => 'Deposit',
                        'withdrawal' => 'Withdrawal',
                    ])
                    ->default('deposit')
                    ->afterStateUpdated(static function (Forms\Set $set, $state) {
                        if ($state === 'deposit') {
                            $account = Account::where('category', AccountCategory::Revenue)
                                ->where('name', 'Uncategorized Income')->first();

                            if ($account->exists()) {
                                $set('account_id', $account->id);
                            }
                        } else {
                            $account = Account::where('category', AccountCategory::Expense)
                                ->where('name', 'Uncategorized Expense')->first();

                            if ($account->exists()) {
                                $set('account_id', $account->id);
                            }
                        }
                    })
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->money(static function (Forms\Get $get) {
                        $bankAccount = $get('bank_account_id');
                        $bankAccount = BankAccount::find($bankAccount);
                        $account = $bankAccount->account ?? null;

                        if ($account) {
                            return $account->currency_code;
                        }

                        return 'USD';
                    })
                    ->required(),
                Forms\Components\Select::make('account_id')
                    ->label('Category')
                    ->options(static fn (Forms\Get $get) => static::getAccountOptions($get('method')))
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
                Tables\Columns\TextColumn::make('bankAccount.account.name')
                    ->label('Account')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->label('Description'),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->color(static fn (Transaction $record) => $record->type === 'expense' ? 'danger' : null)
                    ->currency(static fn (Transaction $record) => $record->bankAccount->account->currency_code ?? 'USD', true),
            ])
            ->defaultSort('posted_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->mutateFormDataUsing(static function (array $data): array {
                        $method = $data['method'];

                        if ($method === 'deposit') {
                            $data['type'] = 'income';
                        } else {
                            $data['type'] = 'expense';
                        }

                        return $data;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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

    public static function getAccountOptions(mixed $method)
    {
        $excludedCategory = match ($method) {
            'deposit' => AccountCategory::Expense,
            'withdrawal' => AccountCategory::Revenue,
        };

        $accounts = Account::whereNot('category', $excludedCategory)->get();

        return $accounts->groupBy(fn (Account $account) => $account->category->getLabel())
            ->map(fn (Collection $accounts, string $category) => $accounts->mapWithKeys(fn (Account $account) => [$account->id => $account->name]))
            ->toArray();
    }
}
