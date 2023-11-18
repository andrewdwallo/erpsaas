<?php

namespace App\Utilities;

use App\Contracts\DocumentNumber as DocumentNumberInterface;
use Illuminate\Database\Eloquent\Model;

class DocumentNumber implements DocumentNumberInterface
{
    public function getNextNumber(?Model $model, ?string $type, int | string $number, string $prefix, int | string $digits, ?bool $padded = false): string
    {
        if ($model) {
            $numberNext = $model?->newQuery()
                ->where('type', $type)
                ->value($number);
        } else {
            $numberNext = $number;
        }

        if ($padded) {
            return $prefix . str_pad($numberNext, $digits, '0', STR_PAD_LEFT);
        }

        return $numberNext;
    }

    public function incrementNumber(Model $model, string $type): void
    {
        $model->newQuery()
            ->where('type', $type)
            ->increment('number_next');
    }
}
