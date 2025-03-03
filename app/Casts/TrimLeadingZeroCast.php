<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Model;

class TrimLeadingZeroCast implements CastsInboundAttributes
{
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return (int) ltrim($value, '0');
    }
}
