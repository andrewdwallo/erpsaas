<?php

namespace App\DTO;

use Livewire\Wireable;

class AccountBalanceDTO implements Wireable
{
    public function __construct(
        public ?string $startingBalance,
        public string $debitBalance,
        public string $creditBalance,
        public ?string $netMovement,
        public ?string $endingBalance,
    ) {
    }

    public function toLivewire(): array
    {
        return [
            'startingBalance' => $this->startingBalance,
            'debitBalance' => $this->debitBalance,
            'creditBalance' => $this->creditBalance,
            'netMovement' => $this->netMovement,
            'endingBalance' => $this->endingBalance,
        ];
    }

    public static function fromLivewire($value): static
    {
        return new static(
            $value['startingBalance'],
            $value['debitBalance'],
            $value['creditBalance'],
            $value['netMovement'],
            $value['endingBalance'],
        );
    }
}
