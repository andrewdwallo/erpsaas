<?php

namespace App\Filament\Company\Resources\Banking;

use App\Actions\OptionAction\CreateCurrency;
use App\Enums\AccountType;
use App\Facades\Forex;
use App\Filament\Company\Resources\Banking\AccountResource\Pages;
use App\Models\Banking\Account;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use Wallo\FilamentSelectify\Components\ToggleButton;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

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
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Account Information')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->options(AccountType::class)
                                    ->localizeLabel()
                                    ->searchable()
                                    ->default(AccountType::DEFAULT)
                                    ->live()
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->maxLength(100)
                                    ->localizeLabel()
                                    ->required(),
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
                                    ->localizeLabel('Default')
                                    ->onLabel(translate('Yes'))
                                    ->offLabel(translate('No'))
                                    ->hidden(static fn (Forms\Get $get) => $get('type') === AccountType::CreditCard->value),
                            ])->columns(),
                        Forms\Components\Section::make('Currency & Balance')
                            ->schema([
                                Forms\Components\Select::make('currency_code')
                                    ->localizeLabel('Currency')
                                    ->relationship('currency', 'name')
                                    ->default(CurrencyAccessor::getDefaultCurrency())
                                    ->saveRelationshipsUsing(null)
                                    ->disabledOn('edit')
                                    ->dehydrated()
                                    ->preload()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(static function (Forms\Set $set, $state, $old, Forms\Get $get) {
                                        $opening_balance = CurrencyConverter::convertAndSet($state, $old, $get('opening_balance'));

                                        if ($opening_balance !== null) {
                                            $set('opening_balance', $opening_balance);
                                        }
                                    })
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\Select::make('currency.code')
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

                                                $set('currency.name', $currency_code->getName() ?? '');

                                                if ($forexEnabled && $exchangeRate !== null) {
                                                    $set('currency.rate', $exchangeRate);
                                                }
                                            })
                                            ->required(),
                                        Forms\Components\TextInput::make('currency.name')
                                            ->localizeLabel()
                                            ->maxLength(100)
                                            ->required(),
                                        Forms\Components\TextInput::make('currency.rate')
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
                                                    $code = $data['currency']['code'];
                                                    $name = $data['currency']['name'];
                                                    $rate = $data['currency']['rate'];

                                                    return (new CreateCurrency())->create($code, $name, $rate);
                                                });
                                            });
                                    }),
                                Forms\Components\TextInput::make('opening_balance')
                                    ->required()
                                    ->localizeLabel()
                                    ->disabledOn('edit')
                                    ->dehydrated()
                                    ->money(static fn (Forms\Get $get) => $get('currency_code')),
                            ])->columns(),
                        Forms\Components\Tabs::make('Account Specifications')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Bank Information')
                                    ->icon('heroicon-o-credit-card')
                                    ->schema([
                                        Forms\Components\TextInput::make('bank_name')
                                            ->localizeLabel()
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('bank_phone')
                                            ->tel()
                                            ->localizeLabel()
                                            ->maxLength(20),
                                        Forms\Components\Textarea::make('bank_address')
                                            ->localizeLabel()
                                            ->columnSpanFull(),
                                    ])->columns(),
                                Forms\Components\Tabs\Tab::make('Additional Information')
                                    ->icon('heroicon-o-information-circle')
                                    ->schema([
                                        Forms\Components\TextInput::make('description')
                                            ->localizeLabel()
                                            ->maxLength(100),
                                        Forms\Components\SpatieTagsInput::make('tags')
                                            ->localizeLabel()
                                            ->placeholder('Enter tags...')
                                            ->type('statuses')
                                            ->suggestions([
                                                'Business',
                                                'Personal',
                                                'College Fund',
                                            ]),
                                        Forms\Components\MarkdownEditor::make('notes')
                                            ->columnSpanFull(),
                                    ])->columns(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Routing Information')
                            ->schema([
                                Forms\Components\TextInput::make('aba_routing_number')
                                    ->localizeLabel('ABA Number')
                                    ->integer()
                                    ->length(9),
                                Forms\Components\TextInput::make('ach_routing_number')
                                    ->localizeLabel('ACH Number')
                                    ->integer()
                                    ->length(9),
                            ]),
                        Forms\Components\Section::make('International Bank Information')
                            ->schema([
                                Forms\Components\TextInput::make('bic_swift_code')
                                    ->localizeLabel('BIC/SWIFT Code')
                                    ->maxLength(11),
                                Forms\Components\TextInput::make('iban')
                                    ->localizeLabel('IBAN')
                                    ->maxLength(34),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->localizeLabel('Account')
                    ->searchable()
                    ->weight(FontWeight::Medium)
                    ->icon(static fn (Account $record) => $record->isEnabled() ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (Account $record) => $record->isEnabled() ? 'Default Account' : null)
                    ->iconPosition('after')
                    ->description(static fn (Account $record) => $record->number ?: 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->localizeLabel('Bank')
                    ->placeholder('N/A')
                    ->description(static fn (Account $record) => $record->bank_phone ?: 'N/A')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->localizeLabel()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->localizeLabel('Current Balance')
                    ->sortable()
                    ->currency(static fn (Account $record) => $record->currency_code, true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('update_balance')
                    ->hidden(function (Account $record) {
                        $usesDefaultCurrency = $record->currency->isEnabled();
                        $forexDisabled = Forex::isDisabled();
                        $sameExchangeRate = $record->currency->rate === $record->currency->live_rate;

                        return $usesDefaultCurrency || $forexDisabled || $sameExchangeRate;
                    })
                    ->label('Update Balance')
                    ->icon('heroicon-o-currency-dollar')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to update the balance with the latest exchange rate?')
                    ->before(static function (Tables\Actions\Action $action, Account $record) {
                        if ($record->currency->isDisabled()) {
                            $defaultCurrency = CurrencyAccessor::getDefaultCurrency();
                            $exchangeRate = Forex::getCachedExchangeRate($defaultCurrency, $record->currency_code);
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
                    ->action(static function (Account $record) {
                        if ($record->currency->isDisabled()) {
                            $defaultCurrency = CurrencyAccessor::getDefaultCurrency();
                            $exchangeRate = Forex::getCachedExchangeRate($defaultCurrency, $record->currency_code);
                            $oldExchangeRate = $record->currency->rate;

                            if ($exchangeRate !== null && $exchangeRate !== $oldExchangeRate) {

                                $scale = 10 ** $record->currency->precision;
                                $cleanedBalance = (int) filter_var($record->opening_balance, FILTER_SANITIZE_NUMBER_INT);

                                $newBalance = ($exchangeRate / $oldExchangeRate) * $cleanedBalance;
                                $newBalanceInt = (int) round($newBalance, $scale);

                                $record->opening_balance = money($newBalanceInt, $record->currency_code)->getValue();
                                $record->currency->rate = $exchangeRate;

                                $record->currency->save();
                                $record->save();

                                Notification::make()
                                    ->success()
                                    ->title('Balance Updated Successfully')
                                    ->body(__('The :name account balance has been updated to reflect the current exchange rate.', ['name' => $record->name]))
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
            ->checkIfRecordIsSelectableUsing(static fn (Account $record) => $record->isDisabled())
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
}
