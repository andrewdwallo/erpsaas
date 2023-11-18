<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentTerms: string implements HasLabel
{
    case DueUponReceipt = 'due_upon_receipt';
    case Net7 = 'net_7';
    case Net10 = 'net_10';
    case Net15 = 'net_15';
    case Net30 = 'net_30';
    case Net60 = 'net_60';
    case Net90 = 'net_90';

    public const DEFAULT = self::DueUponReceipt->value;

    public function getLabel(): ?string
    {
        $label = ucwords(str_replace('_', ' ', $this->value));

        return translate($label);
    }

    public function getDays(): int
    {
        return match ($this) {
            self::DueUponReceipt => 0,
            self::Net7 => 7,
            self::Net10 => 10,
            self::Net15 => 15,
            self::Net30 => 30,
            self::Net60 => 60,
            self::Net90 => 90,
        };
    }

    public function getDueDate(string $format): string
    {
        $days = $this->getDays() ?? 0;

        return now()->addDays($days)->translatedFormat($format);
    }
}
