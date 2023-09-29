<?php

namespace App\Enums;

use Filament\Support\Contracts\{HasIcon, HasLabel};

enum DocumentType: string implements HasIcon, HasLabel
{
    case Invoice = 'invoice';
    case Bill = 'bill';

    public const DEFAULT = self::Invoice->value;

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getIcon(): ?string
    {
        return match ($this->value) {
            self::Invoice->value => 'heroicon-o-document-duplicate',
            self::Bill->value => 'heroicon-o-clipboard-document-list',
        };
    }
}
