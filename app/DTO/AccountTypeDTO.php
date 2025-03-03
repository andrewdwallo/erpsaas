<?php

namespace App\DTO;

class AccountTypeDTO
{
    /**
     * @param  AccountDTO[]  $accounts
     */
    public function __construct(
        public array $accounts,
        public AccountBalanceDTO $summary,
    ) {}
}
