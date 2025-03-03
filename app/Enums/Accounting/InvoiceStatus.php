<?php

namespace App\Enums\Accounting;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Unsent = 'unsent';
    case Sent = 'sent';
    case Viewed = 'viewed';

    case Partial = 'partial';

    case Paid = 'paid';

    case Overdue = 'overdue';

    case Overpaid = 'overpaid';

    case Void = 'void';

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Draft, self::Unsent, self::Void => 'gray',
            self::Sent, self::Viewed => 'primary',
            self::Partial => 'warning',
            self::Paid, self::Overpaid => 'success',
            self::Overdue => 'danger',
        };
    }

    public static function canBeOverdue(): array
    {
        return [
            self::Partial,
            self::Sent,
            self::Unsent,
        ];
    }
}
