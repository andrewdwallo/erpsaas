<?php

namespace App\DTO;

readonly class DocumentLabelDTO
{
    public function __construct(
        public string $title,
        public string $number,
        public string $referenceNumber,
        public string $date,
        public string $dueDate,
        public string $amountDue,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'number' => $this->number,
            'reference_number' => $this->referenceNumber,
            'date' => $this->date,
            'due_date' => $this->dueDate,
            'amount_due' => $this->amountDue,
        ];
    }
}
