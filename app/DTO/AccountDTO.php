<?php

namespace App\DTO;

class AccountDTO
{
    public function __construct(
        public string $accountName,
        public string $accountCode,
        public ?int $accountId,
        public AccountBalanceDTO $balance,
        public ?string $startDate,
        public ?string $endDate,
    ) {}
}
