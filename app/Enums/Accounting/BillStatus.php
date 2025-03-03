<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BillStatus: string implements HasColor, HasLabel
{
    case Overdue = 'overdue';
    case Partial = 'partial';
    case Paid = 'paid';
    case Open = 'open';
    case Void = 'void';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Open => 'info',
            self::Overdue => 'danger',
            self::Partial => 'warning',
            self::Paid => 'success',
            self::Void => 'gray',
        };
    }

    public static function canBeOverdue(): array
    {
        return [
            self::Partial,
            self::Open,
        ];
    }
}
