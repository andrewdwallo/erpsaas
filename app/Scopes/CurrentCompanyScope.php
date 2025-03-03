<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CurrentCompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = session('current_company_id');

        if (! $companyId && app()->runningInConsole()) {
            return;
        }

        if (! $companyId && Auth::check() && Auth::user()->currentCompany) {
            $companyId = Auth::user()->currentCompany->id;
            session(['current_company_id' => $companyId]);
        }

        if (! $companyId) {
            $companyId = Auth::user()->currentCompany->id;
        }

        if ($companyId) {
            $builder->where("{$model->getTable()}.company_id", $companyId);
        } else {
            Log::error('CurrentCompanyScope: No company_id found for user ' . Auth::id());

            throw new ModelNotFoundException('CurrentCompanyScope: No company_id set in the session or on the user.');
        }
    }
}
