<?php

namespace App\Filament\Company\Resources\Accounting;

use App\Enums\DateFormat;
use App\Filament\Company\Resources\Accounting\TransactionResource\Pages;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Setting\Localization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('posted_at')
                    ->label('Posted At')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->options([
                        'expense' => 'Expense',
                        'income' => 'Income',
                        'transfer' => 'Transfer',
                    ])
                    ->required(),
                Forms\Components\Select::make('method')
                    ->label('Method')
                    ->options([
                        'deposit' => 'Deposit',
                        'withdrawal' => 'Withdrawal',
                    ])
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
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Date')
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
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->html()
                    ->formatStateUsing(static function ($state, Transaction $record) {
                        $color = $record->category->color ?? '#000000';

                        return "<span style='display: inline-block; width: 8px; height: 8px; background-color: {$color}; border-radius: 50%; margin-right: 3px;'></span> {$state}";
                    }),
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
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
