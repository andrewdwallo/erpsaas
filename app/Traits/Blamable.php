<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait Blamable
{
    public static function bootBlamable(): void
    {
        static::created(static function ($model) {
            $model->created_by = Auth::check() ? Auth::id() : null;
            $model->updated_by = Auth::check() ? Auth::id() : null;
        });

        static::updated(static function ($model) {
            $model->updated_by = Auth::check() ? Auth::id() : null;
        });
    }
}
