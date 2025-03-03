<?php

namespace App\DTO;

readonly class EntityReportDTO
{
    public function __construct(
        public string $name,
        public string $id,
        public ?AgingBucketDTO $aging = null,
        public ?EntityBalanceDTO $balance = null,
        public ?PaymentMetricsDTO $paymentMetrics = null,
    ) {}
}
