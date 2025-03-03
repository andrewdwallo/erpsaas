<?php

namespace App\Concerns;

use App\Events\CompanyDefaultEvent;

trait SyncsWithCompanyDefaults
{
    public static function bootSyncsWithCompanyDefaults(): void
    {
        static::created(static function ($model) {
            event(new CompanyDefaultEvent($model));
        });

        static::updated(static function ($model) {
            event(new CompanyDefaultEvent($model));
        });
    }
}
