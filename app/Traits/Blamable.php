<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Blamable
{
    public static function bootBlamable(): void
    {
        $auth = Auth::check() ? Auth::id() : null;

        static::creating(static function ($model) use ($auth) {
            $model->created_by = $auth;
            $model->updated_by = $auth;
        });

        static::updating(static function ($model) use ($auth) {
            $model->updated_by = $auth;
        });
    }
}
