<?php

namespace App\Providers;

use App\Models\Banking;
use App\Models\Setting;
use App\Policies\DefaultEnabledRecordPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        $this->registerEnabledRecordPolicy();
    }

    /**
     * Register the policy for the enabled record.
     */
    protected function registerEnabledRecordPolicy(): void
    {
        $models = [
            Setting\Currency::class,
            Setting\Discount::class,
            Setting\Tax::class,
            Banking\BankAccount::class,
        ];

        foreach ($models as $model) {
            Gate::policy($model, DefaultEnabledRecordPolicy::class);
        }
    }
}
