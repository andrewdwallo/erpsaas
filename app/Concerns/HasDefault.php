<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasDefault
{
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    public function isDisabled(): bool
    {
        return $this->enabled === false;
    }

    public static function enabledLabel(): string
    {
        return translate('Yes');
    }

    public static function disabledLabel(): string
    {
        return translate('No');
    }

    public static function bootHasDefault(): void
    {
        static::saving(function (Model $model) {
            if ($model->isDirty(['enabled', $model->evaluatedDefault])) {
                if ($model->enabled === true) {
                    static::query()
                        ->whereKeyNot($model->getKey())
                        ->where('enabled', true)
                        ->when(filled($model->evaluatedDefault), static fn (Builder $query) => $query->where($model->evaluatedDefault, $model->{$model->evaluatedDefault}))
                        ->update(['enabled' => false]);
                } else {
                    $enabledRecordDoesNotExist = static::query()
                        ->whereKeyNot($model->getKey())
                        ->where('enabled', true)
                        ->when(filled($model->evaluatedDefault), static fn (Builder $query) => $query->where($model->evaluatedDefault, $model->{$model->evaluatedDefault}))
                        ->doesntExist();

                    if ($enabledRecordDoesNotExist) {
                        $model->enabled = true;
                    }
                }
            }
        });
    }
}
