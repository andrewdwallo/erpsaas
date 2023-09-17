<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CategoryType: string implements HasLabel
{
    case Expense = 'expense';
    case Income = 'income';
    case Item = 'item';
    case Other = 'other';

    public function getLabel(): ?string
    {
        return $this->name;
    }
}
