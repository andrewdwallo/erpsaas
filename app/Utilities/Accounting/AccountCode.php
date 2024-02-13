<?php

namespace App\Utilities\Accounting;

use App\Enums\Accounting\AccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use RuntimeException;

class AccountCode
{
    public static function isValidCode($code, AccountType $type): bool
    {
        $range = self::getRangeForType($type);

        $mainAccountPart = explode('-', $code)[0];

        $numericValue = (int) $mainAccountPart;

        return $numericValue >= $range[0] && $numericValue <= $range[1];
    }

    public static function getMessage(AccountType $type): string
    {
        $range = self::getRangeForType($type);

        return "The account code must range from {$range[0]} to {$range[1]} for a {$type->getLabel()}.";
    }

    public static function getRangeForType(AccountType $type): array
    {
        return match ($type) {
            AccountType::CurrentAsset => [1000, 1499],
            AccountType::NonCurrentAsset => [1500, 1899],
            AccountType::ContraAsset => [1900, 1999],
            AccountType::CurrentLiability => [2000, 2499],
            AccountType::NonCurrentLiability => [2500, 2899],
            AccountType::ContraLiability => [2900, 2999],
            AccountType::Equity => [3000, 3899],
            AccountType::ContraEquity => [3900, 3999],
            AccountType::OperatingRevenue => [4000, 4499],
            AccountType::NonOperatingRevenue => [4500, 4899],
            AccountType::ContraRevenue => [4900, 4949],
            AccountType::UncategorizedRevenue => [4950, 4999],
            AccountType::OperatingExpense => [5000, 5499],
            AccountType::NonOperatingExpense => [5500, 5899],
            AccountType::ContraExpense => [5900, 5949],
            AccountType::UncategorizedExpense => [5950, 5999],
        };
    }

    public static function generate(int $companyId, string $subtypeId): string
    {
        $subtype = AccountSubtype::find($subtypeId);
        $type = $subtype->type;

        $range = self::getRangeForType($type);

        $lastAccount = Account::where('subtype_id', $subtypeId)
            ->where('company_id', $companyId)
            ->orderBy('code', 'desc')
            ->first(); // maybe handle subaccounts (parent-child) in the future (not using max() because of subaccounts)

        if ($lastAccount) {
            $lastCode = $lastAccount->code;
            $lastAccountPart = explode('-', $lastCode)[0]; // possibly handle subaccounts (parent-child) in the future
            $numericValue = (int) $lastAccountPart;
            $numericValue++;
        } else {
            $numericValue = $range[0];
        }

        while (Account::where('company_id', $companyId)->where('code', '=', (string) $numericValue)->exists() || $numericValue > $range[1]) {
            if ($numericValue > $range[1]) {
                throw new RuntimeException('No more account codes available for this type.');
            }

            $numericValue++;
        }

        return (string) $numericValue;
    }
}
