<?php

namespace App\Enums\Accounting;

use App\Enums\Concerns\ParsesEnum;
use Filament\Support\Contracts\HasLabel;

enum DocumentDiscountMethod: string implements HasLabel
{
    use ParsesEnum;

    case PerLineItem = 'per_line_item';
    case PerDocument = 'per_document';

    public function getLabel(): string
    {
        return match ($this) {
            self::PerLineItem => 'Per Line Item',
            self::PerDocument => 'Per Document',
        };
    }

    public function isPerLineItem(): bool
    {
        return $this == self::PerLineItem;
    }

    public function isPerDocument(): bool
    {
        return $this == self::PerDocument;
    }
}
