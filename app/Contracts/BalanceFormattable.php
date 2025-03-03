<?php

namespace App\Contracts;

interface BalanceFormattable
{
    public static function fromArray(array $balances): static;
}
