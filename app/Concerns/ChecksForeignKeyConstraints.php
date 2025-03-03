<?php

namespace App\Concerns;

trait ChecksForeignKeyConstraints
{
    public static function isForeignKeyUsed($field, $value, array $models): bool
    {
        foreach ($models as $model) {
            $modelInstance = resolve($model);
            if ($modelInstance->where($field, $value)->exists()) {
                return true;
            }
        }

        return false;
    }
}
