<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentTerms: string implements HasLabel
{
    case DueOnReceipt = 'due_on_receipt';
    case Net7 = 'net_7';
    case Net10 = 'net_10';
    case Net15 = 'net_15';
    case Net30 = 'net_30';
    case Net60 = 'net_60';
    case Net90 = 'net_90';

    public const DEFAULT = self::DueOnReceipt->value;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DueOnReceipt => 'Due on Receipt',
            self::Net7 => 'Net 7',
            self::Net10 => 'Net 10',
            self::Net15 => 'Net 15',
            self::Net30 => 'Net 30',
            self::Net60 => 'Net 60',
            self::Net90 => 'Net 90',
        };
    }

    public function getDays(): int
    {
        return match ($this) {
            self::DueOnReceipt => 0,
            self::Net7 => 7,
            self::Net10 => 10,
            self::Net15 => 15,
            self::Net30 => 30,
            self::Net60 => 60,
            self::Net90 => 90,
        };
    }
}
