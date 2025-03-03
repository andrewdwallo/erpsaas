<?php

namespace App\DTO;

use App\Contracts\BalanceFormattable;

readonly class EntityBalanceDTO implements BalanceFormattable
{
    public function __construct(
        public ?string $totalBalance,
        public ?string $paidBalance,
        public ?string $unpaidBalance,
        public ?string $overdueBalance = null,
    ) {}

    public static function fromArray(array $balances): static
    {
        return new static(
            totalBalance: $balances['total_balance'] ?? null,
            paidBalance: $balances['paid_balance'] ?? null,
            unpaidBalance: $balances['unpaid_balance'] ?? null,
            overdueBalance: $balances['overdue_balance'] ?? null,
        );
    }
}
