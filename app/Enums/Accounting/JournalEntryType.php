<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum JournalEntryType: string implements HasLabel
{
    use ParsesEnum;

    case Debit = 'debit';
    case Credit = 'credit';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function isDebit(): bool
    {
        return $this === self::Debit;
    }

    public function isCredit(): bool
    {
        return $this === self::Credit;
    }
}
