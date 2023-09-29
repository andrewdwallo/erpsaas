<?php

namespace App\Providers;

use App\Events\{CompanyDefaultEvent, CompanyDefaultUpdated, CompanyGenerated};
use App\Listeners\{ConfigureCompanyDefault, CreateCompanyDefaults, SyncAssociatedModels, SyncWithCompanyDefaults};
use Filament\Events\TenantSet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CompanyDefaultEvent::class => [
            SyncWithCompanyDefaults::class,
        ],
        CompanyDefaultUpdated::class => [
            SyncAssociatedModels::class,
        ],
        TenantSet::class => [
            ConfigureCompanyDefault::class,
        ],
        CompanyGenerated::class => [
            CreateCompanyDefaults::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
