<?php

namespace App\Casts;

use App\Models\Banking\BankAccount;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class TransactionAmountCast implements CastsAttributes
{
    private array $currencyCache = [];

    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        // Attempt to retrieve the currency code from the related bankAccount->account model
        $currencyCode = $this->getCurrencyCodeFromBankAccountId($attributes['bank_account_id'] ?? null);

        if ($value !== null) {
            return CurrencyConverter::prepareForMutator($value, $currencyCode);
        }

        return '';
    }

    /**
     * @throws UnexpectedValueException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        $currencyCode = $this->getCurrencyCodeFromBankAccountId($attributes['bank_account_id'] ?? null);

        if (is_numeric($value)) {
            $value = (string) $value;
        } elseif (! is_string($value)) {
            throw new UnexpectedValueException('Expected string or numeric value for money cast');
        }

        return CurrencyConverter::prepareForAccessor($value, $currencyCode);
    }

    /**
     * Using this is necessary because the relationship is not always loaded into memory when the cast is called
     * Instead of using: $model->bankAccount->account->currency_code directly, find the bank account and get the currency code
     */
    private function getCurrencyCodeFromBankAccountId(?int $bankAccountId): string
    {
        if ($bankAccountId === null) {
            return CurrencyAccessor::getDefaultCurrency();
        }

        if (isset($this->currencyCache[$bankAccountId])) {
            return $this->currencyCache[$bankAccountId];
        }

        $bankAccount = BankAccount::find($bankAccountId);

        $currencyCode = $bankAccount?->account?->currency_code ?? CurrencyAccessor::getDefaultCurrency();
        $this->currencyCache[$bankAccountId] = $currencyCode;

        return $currencyCode;
    }
}
