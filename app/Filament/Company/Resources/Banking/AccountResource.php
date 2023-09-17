<?php

namespace App\Filament\Company\Resources\Banking;

use App\Actions\OptionAction\CreateCurrency;
use App\Filament\Company\Resources\Banking\AccountResource\Pages;
use App\Filament\Company\Resources\Banking\AccountResource\RelationManagers;
use App\Models\Banking\Account;
use App\Models\Setting\Currency;
use App\Services\CurrencyService;
use App\Utilities\CurrencyConverter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use Wallo\FilamentSelectify\Components\ToggleButton;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Banking';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Account Information')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Type')
                                    ->options(Account::getAccountTypes())
                                    ->searchable()
                                    ->default('checking')
                                    ->live()
                                    ->required(),
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->maxLength(100)
                                    ->required(),
                                Forms\Components\TextInput::make('number')
                                    ->label('Account Number')
                                    ->unique(ignoreRecord: true, modifyRuleUsing: static function (Unique $rule, $state) {
                                        $companyId = Auth::user()->currentCompany->id;

                                        return $rule->where('company_id', $companyId)->where('number', $state);
                                    })
                                    ->maxLength(20)
                                    ->validationAttribute('account number')
                                    ->required(),
                                ToggleButton::make('enabled')
                                    ->label('Default Account')
                                    ->hidden(static fn (Forms\Get $get) => $get('type') === 'credit_card')
                                    ->offColor('danger')
                                    ->onColor('primary'),
                            ])->columns(),
                        Forms\Components\Section::make('Currency & Balance')
                            ->schema([
                                Forms\Components\Select::make('currency_code')
                                    ->label('Currency')
                                    ->relationship('currency', 'name')
                                    ->default(Currency::getDefaultCurrencyCode())
                                    ->saveRelationshipsUsing(null)
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
                                            ->label('Code')
                                            ->searchable()
                                            ->options(Currency::getAvailableCurrencyCodes())
                                            ->live()
                                            ->afterStateUpdated(static function (callable $set, $state) {
                                                if ($state === null) {
                                                    return;
                                                }

                                                $currency_code = currency($state);
                                                $currencyService = app(CurrencyService::class);

                                                $defaultCurrencyCode = Currency::getDefaultCurrencyCode();
                                                $rate = 1;

                                                if ($defaultCurrencyCode !== null) {
                                                    $rate = $currencyService->getCachedExchangeRate($defaultCurrencyCode, $state);
                                                }

                                                $set('currency.name', $currency_code->getName() ?? '');
                                                $set('currency.rate', $rate);
                                            })
                                            ->required(),
                                        Forms\Components\TextInput::make('currency.name')
                                            ->label('Name')
                                            ->maxLength(100)
                                            ->required(),
                                        Forms\Components\TextInput::make('currency.rate')
                                            ->label('Rate')
                                            ->numeric()
                                            ->required(),
                                    ])->createOptionAction(static function (Forms\Components\Actions\Action $action) {
                                        return $action
                                            ->label('Add Currency')
                                            ->modalHeading('Add Currency')
                                            ->modalSubmitActionLabel('Add')
                                            ->slideOver()
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
                                    ->label('Opening Balance')
                                    ->required()
                                    ->currency(static fn (Forms\Get $get) => $get('currency_code'))
                            ])->columns(),
                        Forms\Components\Tabs::make('Account Specifications')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Bank Information')
                                    ->icon('heroicon-o-credit-card')
                                    ->schema([
                                        Forms\Components\TextInput::make('bank_name')
                                            ->label('Bank Name')
                                            ->maxLength(100),
                                        Forms\Components\TextInput::make('bank_phone')
                                            ->label('Bank Phone')
                                            ->tel()
                                            ->maxLength(20),
                                        Forms\Components\Textarea::make('bank_address')
                                            ->label('Bank Address')
                                            ->columnSpanFull(),
                                    ])->columns(),
                                Forms\Components\Tabs\Tab::make('Additional Information')
                                    ->icon('heroicon-o-information-circle')
                                    ->schema([
                                        Forms\Components\TextInput::make('description')
                                            ->label('Description')
                                            ->maxLength(100),
                                        Forms\Components\SpatieTagsInput::make('tags')
                                            ->label('Tags')
                                            ->placeholder('Enter tags...')
                                            ->type('statuses')
                                            ->suggestions([
                                                'Business',
                                                'Personal',
                                                'College Fund',
                                            ]),
                                        Forms\Components\MarkdownEditor::make('notes')
                                            ->label('Notes')
                                            ->columnSpanFull(),
                                    ])->columns(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Routing Information')
                            ->schema([
                                Forms\Components\TextInput::make('aba_routing_number')
                                    ->label('ABA Number')
                                    ->integer()
                                    ->length(9),
                                Forms\Components\TextInput::make('ach_routing_number')
                                    ->label('ACH Number')
                                    ->integer()
                                    ->length(9),
                            ]),
                        Forms\Components\Section::make('International Bank Information')
                            ->schema([
                                Forms\Components\TextInput::make('bic_swift_code')
                                    ->label('BIC/SWIFT Code')
                                    ->maxLength(11),
                                Forms\Components\TextInput::make('iban')
                                    ->label('IBAN')
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
                    ->label('Account')
                    ->searchable()
                    ->weight('semibold')
                    ->icon(static fn (Account $record) => $record->enabled ? 'heroicon-o-lock-closed' : null)
                    ->tooltip(static fn (Account $record) => $record->enabled ? 'Default Account' : null)
                    ->iconPosition('after')
                    ->description(static fn (Account $record) => $record->number ?: 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank')
                    ->placeholder('N/A')
                    ->description(static fn (Account $record) => $record->bank_phone ?: 'N/A')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->label('Status')
                    ->colors([
                        'primary' => 'open',
                        'success' => 'active',
                        'secondary' => 'dormant',
                        'warning' => 'restricted',
                        'danger' => 'closed',
                    ])
                    ->icons([
                        'heroicon-o-currency-dollar' => 'open',
                        'heroicon-o-clock' => 'active',
                        'heroicon-o-status-offline' => 'dormant',
                        'heroicon-o-exclamation' => 'restricted',
                        'heroicon-o-x-circle' => 'closed',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('opening_balance')
                    ->label('Current Balance')
                    ->sortable()
                    ->currency(static fn (Account $record) => $record->currency_code, true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('update_balance')
                    ->hidden(static fn (Account $record) => $record->currency_code === Currency::getDefaultCurrencyCode())
                    ->label('Update Balance')
                    ->icon('heroicon-o-currency-dollar')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to update the balance with the latest exchange rate?')
                    ->before(static function (Tables\Actions\Action $action, Account $record) {
                        if ($record->currency_code !== Currency::getDefaultCurrencyCode()) {
                            $currencyService = app(CurrencyService::class);
                            $defaultCurrency = Currency::getDefaultCurrencyCode();
                            $cachedExchangeRate = $currencyService->getCachedExchangeRate($defaultCurrency, $record->currency_code);
                            $oldExchangeRate = $record->currency->rate;

                            if ($cachedExchangeRate === $oldExchangeRate) {
                                Notification::make()
                                    ->warning()
                                    ->title('Balance Already Up to Date')
                                    ->body(__('The :name account balance is already up to date.', ['name' => $record->name]))
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }
                    })
                    ->action(static function (Account $record) {
                        if ($record->currency_code !== Currency::getDefaultCurrencyCode()) {
                            $currencyService = app(CurrencyService::class);
                            $defaultCurrency = Currency::getDefaultCurrencyCode();
                            $cachedExchangeRate = $currencyService->getCachedExchangeRate($defaultCurrency, $record->currency_code);
                            $oldExchangeRate = $record->currency->rate;

                            if ($cachedExchangeRate !== $oldExchangeRate) {

                                $scale = 10 ** $record->currency->precision;
                                $cleanedBalance = (int)filter_var($record->opening_balance, FILTER_SANITIZE_NUMBER_INT);

                                $newBalance = ($cachedExchangeRate / $oldExchangeRate) * $cleanedBalance;
                                $newBalanceInt = (int)round($newBalance, $scale);

                                $record->opening_balance = money($newBalanceInt, $record->currency_code)->getValue();
                                $record->currency->rate = $cachedExchangeRate;

                                $record->currency->save();
                                $record->save();
                            }

                            Notification::make()
                                ->success()
                                ->title('Balance Updated Successfully')
                                ->body(__('The :name account balance has been updated to reflect the current exchange rate.', ['name' => $record->name]))
                                ->send();
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

    public static function getRelations(): array
    {
        return [
            //
        ];
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
