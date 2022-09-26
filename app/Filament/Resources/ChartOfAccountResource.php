<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChartOfAccountResource\Pages;
use App\Filament\Resources\ChartOfAccountResource\RelationManagers;
use App\Models\ChartOfAccount;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChartOfAccountResource extends Resource
{
    protected static ?string $model = ChartOfAccount::class;

    protected static ?string $navigationGroup = 'Resource Management';
    protected static ?int $navigationSort = 8;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')->relationship('company', 'name')->nullable(),
                Forms\Components\Select::make('department_id')->relationship('department', 'name')->nullable(),
                Forms\Components\Select::make('bank_id')->relationship('bank', 'name')->nullable(),
                Forms\Components\Select::make('account_id')->relationship('account', 'name')->nullable(),
                Forms\Components\Select::make('card_id')->relationship('card', 'name')->nullable(),
                Forms\Components\Select::make('transaction_id')->relationship('transaction', 'name')->nullable(),
                Forms\Components\TextInput::make('reference_number')->maxLength(255),
                Forms\Components\TextInput::make('name')->maxLength(255),
                Forms\Components\TextInput::make('type')->maxLength(255),
                Forms\Components\TextInput::make('balance')->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name', 'name'),
                Tables\Columns\TextColumn::make('department.name', 'name'),
                Tables\Columns\TextColumn::make('bank.name', 'name'),
                Tables\Columns\TextColumn::make('account.name', 'name'),
                Tables\Columns\TextColumn::make('card.name', 'name'),
                Tables\Columns\TextColumn::make('transaction.name', 'name'),
                Tables\Columns\TextColumn::make('reference_number'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('balance'),
                Tables\Columns\TextColumn::make('banks_count')->counts('banks')->label('Banks'),
                Tables\Columns\TextColumn::make('accounts_count')->counts('accounts')->label('Accounts'),
                Tables\Columns\TextColumn::make('cards_count')->counts('cards')->label('Cards'),
                Tables\Columns\TextColumn::make('transactions_count')->counts('transactions')->label('Transactions'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccount::route('/create'),
            'view' => Pages\ViewChartOfAccount::route('/{record}'),
            'edit' => Pages\EditChartOfAccount::route('/{record}/edit'),
        ];
    }    
}
