<?php

namespace App\DTO;

readonly class PaymentMetricsDTO
{
    public function __construct(
        public int $totalDocuments,
        public ?int $onTimeCount,
        public ?int $lateCount,
        public ?int $avgDaysToPay,
        public ?int $avgDaysLate,
        public string $onTimePaymentRate,
    ) {}
}
