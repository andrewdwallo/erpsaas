<?php

namespace App\Providers;

use App\Events\CompanyDefaultEvent;
use App\Events\CompanyDefaultUpdated;
use App\Listeners\ConfigureCompanyDefault;
use App\Listeners\CreateCompanyDefaults;
use App\Listeners\SyncAssociatedModels;
use App\Listeners\SyncWithCompanyDefaults;
use Filament\Events\TenantSet;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Wallo\FilamentCompanies\Events\CompanyCreated;

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
        CompanyCreated::class => [
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
