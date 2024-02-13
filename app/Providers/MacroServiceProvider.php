<?php

namespace App\Providers;

use Akaunting\Money\Currency;
use Akaunting\Money\Money;
use App\Models\Accounting\AccountSubtype;
use App\Utilities\Accounting\AccountCode;
use BackedEnum;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        TextInput::macro('money', function (string | Closure | null $currency = null): static {
            $this->extraAttributes(['wire:key' => Str::random()])
                ->prefix(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);

                    return currency($currency)->getPrefix();
                })
                ->suffix(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);

                    return currency($currency)->getSuffix();
                })
                ->mask(static function (TextInput $component) use ($currency) {
                    $currency = $component->evaluate($currency);

                    return moneyMask($currency);
                });

            return $this;
        });

        TextColumn::macro('currency', function (string | Closure | null $currency = null, ?bool $convert = null): static {
            $this->formatStateUsing(static function (TextColumn $column, $state) use ($currency, $convert): ?string {
                if (blank($state)) {
                    return null;
                }

                $currency = $column->evaluate($currency);
                $convert = $column->evaluate($convert);

                return money($state, $currency, $convert)->formatWithCode();
            });

            return $this;
        });

        TextInput::macro('rate', function (string | Closure | null $computation = null): static {
            $this->extraAttributes(['wire:key' => Str::random()])
                ->prefix(static function (TextInput $component) use ($computation) {
                    $computation = $component->evaluate($computation);

                    return ratePrefix(computation: $computation);
                })
                ->suffix(static function (TextInput $component) use ($computation) {
                    $computation = $component->evaluate($computation);

                    return rateSuffix(computation: $computation);
                })
                ->mask(static function (TextInput $component) use ($computation) {
                    $computation = $component->evaluate($computation);

                    return rateMask(computation: $computation);
                })
                ->rule(static function (TextInput $component) use ($computation) {
                    return static function (string $attribute, $value, Closure $fail) use ($computation, $component) {
                        $computation = $component->evaluate($computation);
                        $numericValue = (float) $value;

                        if ($computation instanceof BackedEnum) {
                            $computation = $computation->value;
                        }

                        if ($computation === 'percentage' || $computation === 'compound') {
                            if ($numericValue < 0 || $numericValue > 100) {
                                $fail(translate('The rate must be between 0 and 100.'));
                            }
                        } elseif ($computation === 'fixed' && $numericValue < 0) {
                            $fail(translate('The rate must be greater than 0.'));
                        }
                    };
                });

            return $this;
        });

        Field::macro('validateAccountCode', function (string | Closure | null $subtype = null): static {
            $this
                ->rules([
                    fn (Field $component): Closure => static function (string $attribute, $value, Closure $fail) use ($subtype, $component) {
                    $subtype = $component->evaluate($subtype);
                    $chartSubtype = AccountSubtype::find($subtype);
                    $type = $chartSubtype->type;

                    if (!AccountCode::isValidCode($value, $type)) {
                        $message = AccountCode::getMessage($type);

                        $fail($message);
                    }
                },
            ]);

            return $this;
        });

        TextColumn::macro('rate', function (string | Closure | null $computation = null): static {
            $this->formatStateUsing(static function (TextColumn $column, $state) use ($computation): ?string {
                $computation = $column->evaluate($computation);

                return rateFormat(state: $state, computation: $computation);
            });

            return $this;
        });

        Field::macro('softRequired', function (): static {
            $this
                ->required()
                ->markAsRequired(false);

            return $this;
        });

        Money::macro('swapAmountFor', function ($newCurrency) {
            $oldCurrency = $this->currency->getCurrency();
            $balance = $this->getAmount();

            $oldRate = currency($oldCurrency)->getRate();
            $newRate = currency($newCurrency)->getRate();

            $ratio = $newRate / $oldRate;

            $convertedBalance = money($balance, $oldCurrency)->multiply($ratio)->getAmount();

            return (int) filter_var($convertedBalance, FILTER_SANITIZE_NUMBER_INT);
        });

        Money::macro('formatWithCode', function () {
            $formatted = $this->formatSimple();

            $isSymbolFirst = $this->currency->isSymbolFirst();

            $currencyCode = $this->currency->getCurrency();

            if ($isSymbolFirst) {
                return $formatted . ' ' . $currencyCode;
            }

            return $currencyCode . ' ' . $formatted;
        });

        Currency::macro('getEntity', function () {
            $currencyCode = $this->getCurrency();

            $entity = config("money.currencies.{$currencyCode}.entity");

            return $entity ?? $currencyCode;
        });

        Currency::macro('getCodePrefix', function () {
            if ($this->isSymbolFirst()) {
                return '';
            }

            return ' ' . $this->getCurrency();
        });

        Currency::macro('getCodeSuffix', function () {
            if ($this->isSymbolFirst()) {
                return ' ' . $this->getCurrency();
            }

            return '';
        });
    }
}
