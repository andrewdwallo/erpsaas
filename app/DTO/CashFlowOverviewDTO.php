<?php

namespace App\DTO;

class CashFlowOverviewDTO
{
    public function __construct(
        public array $categories,
    ) {}
}
