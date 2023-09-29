<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\{Builder, Model, Scope};
use Illuminate\Support\Facades\Auth;

class CurrentCompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->currentCompany) {
            $builder->where('company_id', Auth::user()->currentCompany->id);
        }
    }
}
