<?php

namespace App\DTO;

class ReportCategoryDTO
{
    /**
     * ReportCategoryDTO constructor.
     *
     * @param  ReportTypeDTO[]|null  $types
     */
    public function __construct(
        public ?array $header = null,
        public ?array $data = null,
        public ?array $summary = null,
        public ?array $types = null,
    ) {}
}
