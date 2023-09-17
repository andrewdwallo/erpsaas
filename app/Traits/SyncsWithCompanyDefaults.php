<?php

namespace App\Traits;

use App\Events\CompanyDefaultEvent;

trait SyncsWithCompanyDefaults
{
    public static function bootSyncsWithCompanyDefaults(): void
    {
        static::creating(static function ($model) {
            event(new CompanyDefaultEvent($model));
        });

        static::updating(static function ($model) {
            event(new CompanyDefaultEvent($model));
        });
    }
}
