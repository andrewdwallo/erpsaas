<?php

namespace App\Filament\Company\Resources\Banking;

use App\Actions\OptionAction\CreateCurrency;
use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\BankAccountType;
use App\Facades\Forex;
use App\Filament\Company\Resources\Banking\AccountResource\Pages;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component as Livewire;
use Wallo\FilamentSelectify\Components\ToggleButton;

class AccountResource extends Resource
{
    protected static ?string $model = BankAccount::class;

    protected static ?string $modelLabel = 'Account';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Banking';

    public static function getModelLabel(): string
    {
        $modelLabel = static::$modelLabel;

        return translate($modelLabel);
    }

    public static function getNavigationParentItem(): ?string
    {
        if (Filament::hasTopNavigation()) {
            return translate(static::$navigationGroup);
        }

        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Account Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(BankAccountType::class)
                            ->localizeLabel()
                            ->searchable()
                            ->default(BankAccountType::DEFAULT)
                            ->live()
                            ->afterStateUpdated(static function (Forms\Set $set, $state, ?BankAccount $record, string $operation) {
                                if ($operation === 'create') {
                                    $set('account.subtype_id', null);
                                } elseif ($operation === 'edit' && $record !== null) {
                                    if ($state !== $record->type->value) {
                                        $set('account.subtype_id', null);
                                    } else {
                                        $set('account.subtype_id', $record->account->subtype_id);
                                    }
                                }
                            })
                            ->required(),
                        Forms\Components\Group::make()
                            ->relationship('account')
                            ->schema([
                                Forms\Components\Select::make('subtype_id')
                                    ->options(static function (Forms\Get $get) {
                                        $typeValue = $get('data.type', true); // Bug: $get('type') returns string on edit, but returns Enum type on create
                                        $typeString = $typeValue instanceof BackedEnum ? $typeValue->value : $typeValue;

                                        return static::groupSubtypesBySubtypeType($typeString);
                                    })
                                    ->localizeLabel()
                                    ->searchable()
                                    ->live()
                                    ->required(),
                            ]),
                        Forms\Components\Group::make()
                            ->relationship('account')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->maxLength(100)
                                    ->localizeLabel()
                                    ->required(),
                            ]),
                        Forms\Components\TextInput::make('number')
                            ->localizeLabel('Account Number')
                            ->unique(ignoreRecord: true, modifyRuleUsing: static function (Unique $rule, $state) {
                                $companyId = Auth::user()->currentCompany->id;

                                return $rule->where('company_id', $companyId)->where('number', $state);
                            })
                            ->maxLength(20)
                            ->validationAttribute('account number')
                            ->required(),
                        ToggleButton::make('enabled')
                            ->localizeLabel('Default'),
                    ])->columns(),
                Forms\Components\Section::make('Financial Details')
                    ->relationship('account')
                    ->schema([
                        Forms\Components\Select::make('currency_code')
                            ->localizeLabel('Currency')
                            ->relationship('currency', 'name')
                            ->default(CurrencyAccessor::getDefaultCurrency())
                            ->preload()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(static function (Forms\Set $set, $state, $old, Forms\Get $get) {
                                $starting_balance = CurrencyConverter::convertAndSet($state, $old, $get('starting_balance'));

                                if ($starting_balance !== null) {
                                    $set('starting_balance', $starting_balance);
                                }
                            })
                            ->required()
                            ->createOptionForm([
                                Forms\Components\Select::make('code')
                                    ->localizeLabel()
                                    ->searchable()
                                    ->options(CurrencyAccessor::getAvailableCurrencies())
                                    ->live()
                                    ->afterStateUpdated(static function (callable $set, $state) {
                                        if ($state === null) {
                                            return;
                                        }

                                        $currency_code = currency($state);
                                        $defaultCurrencyCode = currency()->getCurrency();
                                        $forexEnabled = Forex::isEnabled();
                                        $exchangeRate = $forexEnabled ? Forex::getCachedExchangeRate($defaultCurrencyCode, $state) : null;

                                        $set('name', $currency_code->getName() ?? '');

                                        if ($forexEnabled && $exchangeRate !== null) {
                                            $set('rate', $exchangeRate);
                                        }
                                    })
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->localizeLabel()
                                    ->maxLength(100)
                                    ->required(),
                                Forms\Components\TextInput::make('rate')
                                    ->localizeLabel()
                                    ->numeric()
                                    ->required(),
                            ])->createOptionAction(static function (Forms\Components\Actions\Action $action) {
                                return $action
                                    ->label('Add Currency')
                                    ->slideOver()
                                    ->modalWidth('md')
                                    ->action(static function (array $data) {
                                        return DB::transaction(static function () use ($data) {
                                            $code = $data['code'];
                                            $name = $data['name'];
                                            $rate = $data['rate'];

                                            return (new CreateCurrency())->create($code, $name, $rate);
                                        });
                                    });
                            }),
                        Forms\Components\TextInput::make('starting_balance')
                            ->required()
                            ->localizeLabel()
                            ->dehydrated()
                            ->money(static fn (Forms\Get $get) => $get('currency_code')),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.name')
                    ->localizeLabel('Account')
                    ->searchable()
                    ->weight(FontWeight::Medium)
                    ->icon(static fn (BankAccount $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (BankAccount $record) => $record->isEnabled() ? 'Default Account' : null)
                    ->iconPosition('after')
                    ->description(static fn (BankAccount $record) => $record->number ?: 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('account.starting_balance')
                    ->localizeLabel('Current Balance')
                    ->sortable()
                    ->currency(static fn (BankAccount $record) => $record->account->currency_code, true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('update_balance')
                    ->hidden(function (BankAccount $record) {
                        $usesDefaultCurrency = $record->account->currency->isEnabled();
                        $forexDisabled = Forex::isDisabled();
                        $sameExchangeRate = $record->account->currency->rate === $record->account->currency->live_rate;

                        return $usesDefaultCurrency || $forexDisabled || $sameExchangeRate;
                    })
                    ->label('Update Balance')
                    ->icon('heroicon-o-currency-dollar')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to update the balance with the latest exchange rate?')
                    ->before(static function (Tables\Actions\Action $action, BankAccount $record) {
                        if ($record->account->currency->isDisabled()) {
                            $defaultCurrency = CurrencyAccessor::getDefaultCurrency();
                            $exchangeRate = Forex::getCachedExchangeRate($defaultCurrency, $record->account->currency_code);
                            if ($exchangeRate === null) {
                                Notification::make()
                                    ->warning()
                                    ->title(__('Exchange Rate Unavailable'))
                                    ->body(__('The exchange rate for this account is currently unavailable. Please try again later.'))
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }
                    })
                    ->action(static function (BankAccount $record) {
                        if ($record->account->currency->isDisabled()) {
                            $defaultCurrency = CurrencyAccessor::getDefaultCurrency();
                            $exchangeRate = Forex::getCachedExchangeRate($defaultCurrency, $record->account->currency_code);
                            $oldExchangeRate = $record->account->currency->rate;

                            if ($exchangeRate !== null && $exchangeRate !== $oldExchangeRate) {

                                $scale = 10 ** $record->account->currency->precision;
                                $cleanedBalance = (int) filter_var($record->account->starting_balance, FILTER_SANITIZE_NUMBER_INT);

                                $newBalance = ($exchangeRate / $oldExchangeRate) * $cleanedBalance;
                                $newBalanceInt = (int) round($newBalance, $scale);

                                $record->account->starting_balance = money($newBalanceInt, $record->account->currency_code)->getValue();
                                $record->account->currency->rate = $exchangeRate;

                                $record->account->currency->save();
                                $record->save();

                                Notification::make()
                                    ->success()
                                    ->title('Balance Updated Successfully')
                                    ->body(__('The :name account balance has been updated to reflect the current exchange rate.', ['name' => $record->account->name]))
                                    ->send();
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }

    public static function groupSubtypesBySubtypeType($typeString): array
    {
        $category = match ($typeString) {
            BankAccountType::Depository->value, BankAccountType::Investment->value => AccountCategory::Asset,
            BankAccountType::Credit->value, BankAccountType::Loan->value => AccountCategory::Liability,
            default => null,
        };

        if ($category === null) {
            return [];
        }

        $subtypes = AccountSubtype::where('category', $category)->get();

        return $subtypes->groupBy(fn(AccountSubtype $subtype) => $subtype->type->getLabel())
            ->map(fn(Collection $subtypes, string $type) => $subtypes->mapWithKeys(static fn (AccountSubtype $subtype) => [$subtype->id => $subtype->name]))
            ->toArray();
    }
}
