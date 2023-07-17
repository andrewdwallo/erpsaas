<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Blamable
{
    public static function bootBlamable(): void
    {
        static::creating(static function ($model) {
            $model->created_by = Auth::check() ? Auth::id() : null;
            $model->updated_by = Auth::check() ? Auth::id() : null;
        });

        static::updating(static function ($model) {
            $model->updated_by = Auth::check() ? Auth::id() : null;
        });
    }
}
