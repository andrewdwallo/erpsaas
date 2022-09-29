<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CardResource\Pages;
use App\Filament\Resources\CardResource\RelationManagers;
use App\Models\Card;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static ?string $navigationGroup = 'Resource Management';
    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')->relationship('company', 'name')->nullable(),
                Forms\Components\Select::make('department_id')->relationship('department', 'name')->nullable(),
                Forms\Components\Select::make('bank_id')->relationship('bank', 'bank_name')->nullable()->label('Bank Name'),
                Forms\Components\Select::make('account_id')->relationship('account', 'account_name')->nullable()->label('Account Name'),
                Forms\Components\TextInput::make('card_type')->nullable(),
                Forms\Components\TextInput::make('card_name')->nullable(),
                Forms\Components\TextInput::make('card_number')->nullable(),
                Forms\Components\TextInput::make('name_on_card')->nullable(),
                Forms\Components\TextInput::make('expiration_date')->nullable(),
                Forms\Components\TextInput::make('security_code')->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name', 'name'),
                Tables\Columns\TextColumn::make('department.name', 'name'),
                Tables\Columns\TextColumn::make('bank.bank_name', 'bank_name')->label('Bank Name'),
                Tables\Columns\TextColumn::make('account.account_name', 'account_name')->label('Account Name'),
                Tables\Columns\TextColumn::make('card_type'),
                Tables\Columns\TextColumn::make('card_name'),
                Tables\Columns\TextColumn::make('card_number'),
                Tables\Columns\TextColumn::make('name_on_card'),
                Tables\Columns\TextColumn::make('expiration_date'),
                Tables\Columns\TextColumn::make('security_code'),
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
            'index' => Pages\ListCards::route('/'),
            'create' => Pages\CreateCard::route('/create'),
            'view' => Pages\ViewCard::route('/{record}'),
            'edit' => Pages\EditCard::route('/{record}/edit'),
        ];
    }    
}
