<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

interface DocumentNumber
{
    public function getNextNumber(?Model $model, ?string $type, int | string $number, string $prefix, int | string $digits, ?bool $padded = true): string;

    public function incrementNumber(Model $model, string $type): void;
}
