<?php

namespace App\DTO;

class AccountCategoryDTO
{
    /**
     * @param  AccountDTO[]|null  $accounts
     * @param  AccountTypeDTO[]|null  $types
     */
    public function __construct(
        public ?array $accounts = null,
        public ?array $types = null,
        public ?AccountBalanceDTO $summary = null,
    ) {}
}
