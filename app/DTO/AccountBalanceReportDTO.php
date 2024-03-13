<?php

namespace App\DTO;

use Livewire\Wireable;

class AccountBalanceReportDTO implements Wireable
{
    public function __construct(
        /**
         * @var AccountCategoryDTO[]
         */
        public array $categories,
        public AccountBalanceDTO $overallTotal,
    ) {
    }

    public function toLivewire(): array
    {
        return [
            'categories' => $this->categories,
            'overallTotal' => $this->overallTotal->toLivewire(),
        ];
    }

    public static function fromLivewire($value): static
    {
        return new static(
            $value['categories'],
            AccountBalanceDTO::fromLivewire($value['overallTotal']),
        );
    }
}
