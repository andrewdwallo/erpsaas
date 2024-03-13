<?php

namespace App\ValueObjects;

class BalanceValue
{
    private int $value;
    private string $currency;

    public function __construct(int $value, string $currency = 'USD')
    {
        $this->value = $value;
        $this->currency = $currency;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function formatted(): string
    {
        return money($this->value, $this->currency)->format();
    }
}
