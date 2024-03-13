<?php

namespace App\DTO;

use Livewire\Wireable;

class AccountCategoryDTO implements Wireable
{
    /**
     * @param  AccountDTO[]  $accounts
     */
    public function __construct(
        public array $accounts,
        public AccountBalanceDTO $summary,
    ) {
    }

    public function toLivewire(): array
    {
        return [
            'accounts' => $this->accounts,
            'summary' => $this->summary->toLivewire(),
        ];
    }

    public static function fromLivewire($value): static
    {
        return new static(
            $value['accounts'],
            AccountBalanceDTO::fromLivewire($value['summary']),
        );
    }
}
