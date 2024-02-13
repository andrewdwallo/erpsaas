<?php

namespace App\Filament\Company\Resources\Setting;

use App\Enums\Accounting\AccountCategory;
use App\Enums\CategoryType;
use App\Filament\Company\Resources\Setting\CategoryResource\Pages;
use App\Models\Accounting\Account;
use App\Models\Setting\Category;
use BackedEnum;
use Closure;
use Exception;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Wallo\FilamentSelectify\Components\ToggleButton;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $modelLabel = 'Category';

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $slug = 'settings/categories';

    public static function getModelLabel(): string
    {
        $modelLabel = static::$modelLabel;

        return translate($modelLabel);
    }

    public static function getNavigationParentItem(): ?string
    {
        if (Filament::hasTopNavigation()) {
            return translate('Finance');
        }

        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->localizeLabel()
                    ->required()
                    ->maxLength(255)
                    ->rule(static function (Forms\Get $get, Forms\Components\Component $component): Closure {
                        return static function (string $attribute, $value, Closure $fail) use ($get, $component) {
                            $existingCategory = Category::where('company_id', auth()->user()->currentCompany->id)
                                ->where('name', $value)
                                ->where('type', $get('type'))
                                ->first();

                            if ($existingCategory && $existingCategory->getKey() !== $component->getRecord()?->getKey()) {
                                $message = translate('The :Type :record ":name" already exists.', [
                                    'Type' => $existingCategory->type->getLabel(),
                                    'record' => strtolower(static::getModelLabel()),
                                    'name' => $value,
                                ]);

                                $fail($message);
                            }
                        };
                    }),
                Forms\Components\Select::make('type')
                    ->localizeLabel()
                    ->options(CategoryType::class)
                    ->live()
                    ->required()
                    ->afterStateUpdated(static function (Forms\Set $set, $state, ?Category $record, string $operation) {
                        $accountOptions = static::getAccountOptions($state);

                        if ($operation === 'create') {
                            $set('account_id', null);
                        } elseif ($operation === 'edit' && $record !== null) {
                            if (! array_key_exists($record->account_id, $accountOptions)) {
                                $set('account_id', null);
                            } else {
                                $set('account_id', $record->account_id);
                            }
                        }
                    }),
                Forms\Components\Select::make('account_id')
                    ->label('Account')
                    ->searchable()
                    ->preload()
                    ->options(static function (Forms\Get $get) {
                        return static::getAccountOptions($get('type'));
                    }),
                Forms\Components\ColorPicker::make('color')
                    ->localizeLabel()
                    ->required(),
                ToggleButton::make('enabled')
                    ->localizeLabel('Default')
                    ->onLabel(Category::enabledLabel())
                    ->offLabel(Category::disabledLabel()),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->localizeLabel()
                    ->weight(FontWeight::Medium)
                    ->icon(static fn (Category $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static function (Category $record) {
                        $tooltipMessage = translate('Default :Type :Record', [
                            'Type' => $record->type->getLabel(),
                            'Record' => static::getModelLabel(),
                        ]);

                        return $record->isEnabled() ? $tooltipMessage : null;
                    })
                    ->iconPosition('after')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->localizeLabel()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')
                    ->localizeLabel()
                    ->copyable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->multiple()
                    ->searchable()
                    ->options(CategoryType::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(static function (Category $record) {
                return $record->isDisabled();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategory::route('/'),
        ];
    }

    public static function getAccountOptions($typeValue): array
    {
        $typeString = $typeValue instanceof BackedEnum ? $typeValue->value : $typeValue;

        $accountCategory = match ($typeString) {
            CategoryType::Income->value => AccountCategory::Revenue,
            CategoryType::Expense->value => AccountCategory::Expense,
            default => null,
        };

        if ($accountCategory) {
            $accounts = Account::where('category', $accountCategory)->get();
        } else {
            $accounts = Account::all();
        }

        return $accounts->pluck('name', 'id')->toArray();
    }
}
