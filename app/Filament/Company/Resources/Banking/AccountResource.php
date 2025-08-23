<?php

namespace App\Filament\Company\Resources\Banking;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Banking\BankAccountType;
use App\Filament\Company\Resources\Banking\AccountResource\Pages\CreateAccount;
use App\Filament\Company\Resources\Banking\AccountResource\Pages\EditAccount;
use App\Filament\Company\Resources\Banking\AccountResource\Pages\ListAccounts;
use App\Filament\Forms\Components\CreateCurrencySelect;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Unique;
use Wallo\FilamentSelectify\Components\ToggleButton;

class AccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $modelLabel = 'account';

    public static function getModelLabel(): string
    {
        $modelLabel = static::$modelLabel;

        return translate($modelLabel);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Information')
                    ->schema([
                        Select::make('type')
                            ->options(BankAccountType::class)
                            ->localizeLabel()
                            ->searchable()
                            ->columnSpan(1)
                            ->disabledOn('edit')
                            ->default(BankAccountType::DEFAULT)
                            ->live()
                            ->afterStateUpdated(static function (Set $set, $state, ?BankAccount $bankAccount, string $operation) {
                                if ($operation === 'create') {
                                    $set('account.subtype_id', null);
                                } elseif ($operation === 'edit' && $bankAccount !== null) {
                                    if ($state !== $bankAccount->type->value) {
                                        $set('account.subtype_id', null);
                                    } else {
                                        $set('account.subtype_id', $bankAccount->account->subtype_id);
                                    }
                                }
                            })
                            ->required(),
                        Group::make()
                            ->columnStart([
                                'default' => 1,
                                'lg' => 2,
                            ])
                            ->relationship('account')
                            ->schema([
                                Select::make('subtype_id')
                                    ->options(static fn (Get $get) => static::groupSubtypesBySubtypeType(BankAccountType::parse($get('data.type', true))))
                                    ->disabledOn('edit')
                                    ->localizeLabel()
                                    ->searchable()
                                    ->live()
                                    ->required(),
                            ]),
                        Group::make()
                            ->relationship('account')
                            ->columns()
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')
                                    ->maxLength(100)
                                    ->localizeLabel()
                                    ->required(),
                                CreateCurrencySelect::make('currency_code')
                                    ->disabledOn('edit'),
                            ]),
                        Group::make()
                            ->columns()
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('number')
                                    ->localizeLabel('Account number')
                                    ->unique(ignoreRecord: true, modifyRuleUsing: static function (Unique $rule, $state) {
                                        $companyId = Auth::user()->currentCompany->id;

                                        return $rule->where('company_id', $companyId)->where('number', $state);
                                    })
                                    ->maxLength(20)
                                    ->validationAttribute('account number'),
                                ToggleButton::make('enabled')
                                    ->localizeLabel('Default'),
                            ]),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with([
                    'account',
                    'account.subtype',
                ]);
            })
            ->columns([
                TextColumn::make('account.name')
                    ->localizeLabel('Account')
                    ->searchable()
                    ->weight(FontWeight::Medium)
                    ->icon(static fn (BankAccount $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (BankAccount $record) => $record->isEnabled() ? 'Default Account' : null)
                    ->iconPosition('after')
                    ->description(static fn (BankAccount $record) => $record->mask ?? null),
                TextColumn::make('account.subtype.name')
                    ->localizeLabel('Subtype')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('account.ending_balance')
                    ->localizeLabel('Ending balance')
                    ->state(static fn (BankAccount $record) => $record->account->ending_balance->convert()->formatWithCode())
                    ->toggleable()
                    ->alignment(Alignment::End),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete the selected accounts? All transactions associated with the accounts will be deleted as well.')
                        ->hidden(function (Table $table) {
                            return $table->getAllSelectableRecordsCount() === 0;
                        }),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(static function (BankAccount $record) {
                return $record->isDisabled();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }

    public static function groupSubtypesBySubtypeType(BankAccountType $bankAccountType): array
    {
        $category = match ($bankAccountType) {
            BankAccountType::Depository, BankAccountType::Investment => AccountCategory::Asset,
            BankAccountType::Credit, BankAccountType::Loan => AccountCategory::Liability,
            default => null,
        };

        if ($category === null) {
            return [];
        }

        $subtypes = AccountSubtype::where('category', $category)->get();

        return $subtypes->groupBy(fn (AccountSubtype $subtype) => $subtype->type->getLabel())
            ->map(fn (Collection $subtypes, string $type) => $subtypes->mapWithKeys(static fn (AccountSubtype $subtype) => [$subtype->id => $subtype->name]))
            ->toArray();
    }
}
