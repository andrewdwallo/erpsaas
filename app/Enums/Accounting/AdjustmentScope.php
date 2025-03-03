<?php

namespace App\Enums\Accounting;

enum AdjustmentScope: string
{
    case Product = 'product';
    case Service = 'service';

    public function getLabel(): ?string
    {
        return translate($this->name);
    }
}
