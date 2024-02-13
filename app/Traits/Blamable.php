<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Blamable
{
    public static function bootBlamable(): void
    {
        static::creating(static function ($model) {
            $auth = Auth::id();
            $model->created_by = $auth;
            $model->updated_by = $auth;
        });

        static::updating(static function ($model) {
            $auth = Auth::id();
            $model->updated_by = $auth;
        });
    }
}
