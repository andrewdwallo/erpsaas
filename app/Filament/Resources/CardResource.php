<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CardResource\Pages;
use App\Filament\Resources\CardResource\RelationManagers;
use App\Models\Card;
use App\Models\Company;
use App\Models\Department;
use App\Models\Bank;
use App\Models\Account;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\MultiSelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static ?string $navigationGroup = 'Bank';
    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                ->label('Company')
                ->options(Company::all()->pluck('name', 'id')->toArray())
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

                Forms\Components\Select::make('department_id')
                ->label('Department')
                ->options(function (callable $get) {
                    $company = Company::find($get('company_id'));

                    if (! $company) {
                        return Department::all()->pluck('name', 'id');
                    }

                    return $company->departments->pluck('name', 'id');
                })
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('bank_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

                Forms\Components\Select::make('bank_id')
                ->label('Bank Name')
                ->options(function (callable $get) {
                    $department = Department::find($get('department_id'));

                    if (! $department) {
                        return Bank::all()->pluck('bank_name', 'id');
                    }

                    return $department->banks->pluck('bank_name', 'id');
                })
                ->reactive()
                ->afterStateUpdated(fn (callable $set) => $set('department_id', null))
                ->afterStateUpdated(fn (callable $set) => $set('account_id', null)),

                Forms\Components\Select::make('account_id')
                ->label('Account Name')
                ->options(function (callable $get) {
                    $bank = Bank::find($get('bank_id'));

                    if (! $bank) {
                        return Account::all()->pluck('account_name', 'id');
                    }

                    return $bank->accounts->pluck('account_name', 'id');
                }),

                Forms\Components\TextInput::make('card_name')->placeholder('MasterCard, Visa, etc...')->label('Card Network'),
                Forms\Components\TextInput::make('card_number')->nullable()->placeholder('1111 2222 3333 4444')->label('Card Number'),
                Forms\Components\TextInput::make('name_on_card')->label('Name On Card'),
                Forms\Components\TextInput::make('expiration_date')->mask(fn (TextInput\Mask $mask) => $mask->pattern('00/0000'))->placeholder('05/2025')->label('Expiration Date'),
                Forms\Components\TextInput::make('security_code')->numeric()->mask(fn (TextInput\Mask $mask) => $mask->range()->from(100)->to(9999)->maxLength(4))->placeholder('123')->label('CVV'),
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
                Tables\Columns\TextColumn::make('card_name')->label('Card Name'),
                Tables\Columns\TextColumn::make('card_number')->label('Card Number'),
                Tables\Columns\TextColumn::make('name_on_card')->label('Name On Card'),
                Tables\Columns\TextColumn::make('expiration_date')->formatStateUsing(fn ($record) => vsprintf('%d%d/%d%d%d%d', str_split($record->expiration_date)))->label('Expiration Date'),
                Tables\Columns\TextColumn::make('security_code')->label('CVV'),
            ])
            ->filters([

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
