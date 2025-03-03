<?php

namespace App\Filament\Company\Resources\Common;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\Common\OfferingType;
use App\Filament\Company\Resources\Common\OfferingResource\Pages;
use App\Models\Accounting\Account;
use App\Models\Common\Offering;
use App\Utilities\Currency\CurrencyAccessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use JaOcero\RadioDeck\Forms\Components\RadioDeck;

class OfferingResource extends Resource
{
    protected static ?string $model = Offering::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        RadioDeck::make('type')
                            ->options(OfferingType::class)
                            ->default(OfferingType::Product)
                            ->icons(OfferingType::class)
                            ->color('primary')
                            ->columns()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->autofocus()
                            ->required()
                            ->columnStart(1)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->money(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->columnSpan(2)
                            ->rows(3),
                        Forms\Components\CheckboxList::make('attributes')
                            ->options([
                                'Sellable' => 'Sellable',
                                'Purchasable' => 'Purchasable',
                            ])
                            ->hiddenLabel()
                            ->required()
                            ->live()
                            ->bulkToggleable()
                            ->validationMessages([
                                'required' => 'The offering must be either sellable or purchasable.',
                            ]),
                    ])->columns(),
                // Sellable Section
                Forms\Components\Section::make('Sale Information')
                    ->schema([
                        Forms\Components\Select::make('income_account_id')
                            ->label('Income account')
                            ->options(Account::query()
                                ->where('category', AccountCategory::Revenue)
                                ->where('type', AccountType::OperatingRevenue)
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => 'The income account is required for sellable offerings.',
                            ]),
                        Forms\Components\Select::make('salesTaxes')
                            ->label('Sales tax')
                            ->relationship('salesTaxes', 'name')
                            ->preload()
                            ->multiple(),
                        Forms\Components\Select::make('salesDiscounts')
                            ->label('Sales discount')
                            ->relationship('salesDiscounts', 'name')
                            ->preload()
                            ->multiple(),
                    ])
                    ->columns()
                    ->visible(fn (Forms\Get $get) => in_array('Sellable', $get('attributes') ?? [])),

                // Purchasable Section
                Forms\Components\Section::make('Purchase Information')
                    ->schema([
                        Forms\Components\Select::make('expense_account_id')
                            ->label('Expense account')
                            ->options(Account::query()
                                ->where('category', AccountCategory::Expense)
                                ->where('type', AccountType::OperatingExpense)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->validationMessages([
                                'required' => 'The expense account is required for purchasable offerings.',
                            ]),
                        Forms\Components\Select::make('purchaseTaxes')
                            ->label('Purchase tax')
                            ->relationship('purchaseTaxes', 'name')
                            ->preload()
                            ->multiple(),
                        Forms\Components\Select::make('purchaseDiscounts')
                            ->label('Purchase discount')
                            ->relationship('purchaseDiscounts', 'name')
                            ->preload()
                            ->multiple(),
                    ])
                    ->columns()
                    ->visible(fn (Forms\Get $get) => in_array('Purchasable', $get('attributes') ?? [])),
            ])->columns();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->selectRaw("
                        *,
                        CONCAT_WS(' & ',
                            CASE WHEN sellable THEN 'Sellable' END,
                            CASE WHEN purchasable THEN 'Purchasable' END
                        ) AS attributes
                    ");
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('attributes')
                    ->label('Attributes')
                    ->badge(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->currency(CurrencyAccessor::getDefaultCurrency(), true)
                    ->sortable()
                    ->description(function (Offering $record) {
                        $adjustments = $record->adjustments()
                            ->pluck('name')
                            ->join(', ');

                        if (empty($adjustments)) {
                            return null;
                        }

                        $adjustmentsList = Str::of($adjustments)->limit(40);

                        return "+ {$adjustmentsList}";
                    }),
            ])
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
            'index' => Pages\ListOfferings::route('/'),
            'create' => Pages\CreateOffering::route('/create'),
            'edit' => Pages\EditOffering::route('/{record}/edit'),
        ];
    }
}
