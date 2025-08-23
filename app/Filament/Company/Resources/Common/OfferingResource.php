<?php

namespace App\Filament\Company\Resources\Common;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\AdjustmentCategory;
use App\Enums\Accounting\AdjustmentType;
use App\Enums\Common\OfferingType;
use App\Filament\Company\Resources\Common\OfferingResource\Pages\CreateOffering;
use App\Filament\Company\Resources\Common\OfferingResource\Pages\EditOffering;
use App\Filament\Company\Resources\Common\OfferingResource\Pages\ListOfferings;
use App\Filament\Forms\Components\Banner;
use App\Filament\Forms\Components\CreateAccountSelect;
use App\Filament\Forms\Components\CreateAdjustmentSelect;
use App\Models\Common\Offering;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use JaOcero\RadioDeck\Forms\Components\RadioDeck;

class OfferingResource extends Resource
{
    protected static ?string $model = Offering::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Banner::make('inactiveAdjustments')
                    ->label('Inactive adjustments')
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle')
                    ->visible(fn (?Offering $record) => $record?->hasInactiveAdjustments())
                    ->columnSpanFull()
                    ->description(function (Offering $record) {
                        $inactiveAdjustments = collect();

                        foreach ($record->adjustments as $adjustment) {
                            if ($adjustment->isInactive() && $inactiveAdjustments->doesntContain($adjustment->name)) {
                                $inactiveAdjustments->push($adjustment->name);
                            }
                        }

                        $adjustmentsList = $inactiveAdjustments->map(static function ($name) {
                            return "<span class='font-medium'>{$name}</span>";
                        })->join(', ');

                        $output = "<p class='text-sm'>This offering contains inactive adjustments that need to be addressed: {$adjustmentsList}</p>";

                        return new HtmlString($output);
                    }),
                static::getGeneralSection(),
                // Sellable Section
                static::getSellableSection(),
                // Purchasable Section
                static::getPurchasableSection(),
            ])->columns();
    }

    public static function getGeneralSection(bool $hasAttributeChoices = true): Section
    {
        return Section::make('General')
            ->schema([
                RadioDeck::make('type')
                    ->options(OfferingType::class)
                    ->default(OfferingType::Product)
                    ->icons(OfferingType::class)
                    ->color('primary')
                    ->columns()
                    ->required(),
                TextInput::make('name')
                    ->autofocus()
                    ->required()
                    ->columnStart(1)
                    ->maxLength(255),
                TextInput::make('price')
                    ->required()
                    ->money(),
                Textarea::make('description')
                    ->label('Description')
                    ->columnSpan(2)
                    ->rows(3),
                CheckboxList::make('attributes')
                    ->options([
                        'Sellable' => 'Sellable',
                        'Purchasable' => 'Purchasable',
                    ])
                    ->visible($hasAttributeChoices)
                    ->hiddenLabel()
                    ->required()
                    ->live()
                    ->bulkToggleable()
                    ->validationMessages([
                        'required' => 'The offering must be either sellable or purchasable.',
                    ]),
            ])->columns();
    }

    public static function getSellableSection(): Section
    {
        return Section::make('Sale Information')
            ->schema([
                CreateAccountSelect::make('income_account_id')
                    ->label('Income account')
                    ->category(AccountCategory::Revenue)
                    ->type(AccountType::OperatingRevenue)
                    ->required()
                    ->validationMessages([
                        'required' => 'The income account is required for sellable offerings.',
                    ]),
                CreateAdjustmentSelect::make('salesTaxes')
                    ->label('Sales tax')
                    ->category(AdjustmentCategory::Tax)
                    ->type(AdjustmentType::Sales)
                    ->multiple(),
                CreateAdjustmentSelect::make('salesDiscounts')
                    ->label('Sales discount')
                    ->category(AdjustmentCategory::Discount)
                    ->type(AdjustmentType::Sales)
                    ->multiple(),
            ])
            ->columns()
            ->visible(static fn (Get $get) => in_array('Sellable', $get('attributes') ?? []));
    }

    public static function getPurchasableSection(): Section
    {
        return Section::make('Purchase Information')
            ->schema([
                CreateAccountSelect::make('expense_account_id')
                    ->label('Expense account')
                    ->category(AccountCategory::Expense)
                    ->type(AccountType::OperatingExpense)
                    ->required()
                    ->validationMessages([
                        'required' => 'The expense account is required for purchasable offerings.',
                    ]),
                CreateAdjustmentSelect::make('purchaseTaxes')
                    ->label('Purchase tax')
                    ->category(AdjustmentCategory::Tax)
                    ->type(AdjustmentType::Purchase)
                    ->multiple(),
                CreateAdjustmentSelect::make('purchaseDiscounts')
                    ->label('Purchase discount')
                    ->category(AdjustmentCategory::Discount)
                    ->type(AdjustmentType::Purchase)
                    ->multiple(),
            ])
            ->columns()
            ->visible(static fn (Get $get) => in_array('Purchasable', $get('attributes') ?? []));
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
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('attributes')
                    ->label('Attributes')
                    ->badge(),
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('price')
                    ->currency()
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListOfferings::route('/'),
            'create' => CreateOffering::route('/create'),
            'edit' => EditOffering::route('/{record}/edit'),
        ];
    }
}
