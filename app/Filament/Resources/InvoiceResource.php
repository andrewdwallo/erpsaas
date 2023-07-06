<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use Wallo\FilamentSelectify\Components\ButtonGroup;
use App\Models\Banking\Account;
use App\Models\Document\Document;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class InvoiceResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Invoices';

    protected static ?string $modelLabel = 'invoice';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'invoice')
            ->where('company_id', Auth::user()->currentCompany->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Billing')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('contact_id')
                                    ->label('Customer')
                                    ->preload()
                                    ->placeholder('Select a customer')
                                    ->relationship('contact', 'name', static fn (Builder $query) => $query->where('type', 'customer')->where('company_id', Auth::user()->currentCompany->id))
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        ButtonGroup::make('contact.entity')
                                            ->label('Entity')
                                            ->options([
                                                'company' => 'Company',
                                                'individual' => 'Individual',
                                            ])
                                            ->default('company')
                                            ->required(),
                                        Forms\Components\TextInput::make('contact.name')
                                            ->label('Name')
                                            ->maxLength(100)
                                            ->required(),
                                        Forms\Components\TextInput::make('contact.email')
                                            ->label('Email')
                                            ->email()
                                            ->nullable(),
                                        Forms\Components\TextInput::make('contact.phone')
                                            ->label('Phone')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\Select::make('contact.currency_code')
                                            ->label('Currency')
                                            ->relationship('currency', 'name', static fn (Builder $query) => $query->where('company_id', Auth::user()->currentCompany->id))
                                            ->preload()
                                            ->default(Account::getDefaultCurrencyCode())
                                            ->searchable()
                                            ->reactive()
                                            ->required(),
                                    ])->columnSpan(1),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('document_date')
                                            ->label('Invoice Date')
                                            ->default(now())
                                            ->format('Y-m-d')
                                            ->required(),
                                        Forms\Components\DatePicker::make('due_date')
                                            ->label('Due Date')
                                            ->default(now())
                                            ->format('Y-m-d')
                                            ->required(),
                                        Forms\Components\TextInput::make('document_number')
                                            ->label('Invoice Number')
                                            ->required(),
                                        Forms\Components\TextInput::make('order_number')
                                            ->label('Order Number')
                                            ->nullable(),
                                    ])->columnSpan(2),
                            ])->columns(3),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DocumentItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
