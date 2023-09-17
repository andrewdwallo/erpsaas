<?php

namespace App\Enums\Concerns;

trait Utilities
{
    public static function caseValues(): array
    {
        return array_column(static::cases(), 'value');
    }

    public static function caseNames(): array
    {
        return array_column(static::cases(), 'name');
    }

    public static function constantNames(): array
    {
        $allConstants = array_keys((new \ReflectionClass(static::class))->getConstants());
        $caseNames = static::caseNames();

        return array_values(array_diff($allConstants, $caseNames));
    }

    public static function constantValues(): array
    {
        $allConstants = array_values((new \ReflectionClass(static::class))->getConstants());
        $caseValues = static::caseValues();

        return array_values(array_diff_key($allConstants, $caseValues));
    }
}
