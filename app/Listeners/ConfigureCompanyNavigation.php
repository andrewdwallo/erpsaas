<?php

namespace App\Listeners;

use App\Events\CompanyConfigured;
use App\Filament\Company\Pages\Accounting\AccountChart;
use App\Filament\Company\Pages\Reports;
use App\Filament\Company\Pages\Service\ConnectedAccount;
use App\Filament\Company\Pages\Service\LiveCurrency;
use App\Filament\Company\Pages\Setting\Appearance;
use App\Filament\Company\Pages\Setting\CompanyDefault;
use App\Filament\Company\Pages\Setting\CompanyProfile;
use App\Filament\Company\Pages\Setting\Invoice;
use App\Filament\Company\Pages\Setting\Localization;
use App\Filament\Company\Resources\Accounting\TransactionResource;
use App\Filament\Company\Resources\Banking\AccountResource;
use App\Filament\Company\Resources\Core\DepartmentResource;
use App\Filament\Company\Resources\Setting\CurrencyResource;
use App\Filament\Company\Resources\Setting\DiscountResource;
use App\Filament\Company\Resources\Setting\TaxResource;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;

class ConfigureCompanyNavigation
{
    /**
     * Handle the event.
     */
    public function handle(CompanyConfigured $event): void
    {
        $company = $event->company;

        $hasTopNavigation = $company->appearance->has_top_navigation ?? false;

        Filament::getPanel('company')
            ->topNavigation($hasTopNavigation)
            ->sidebarCollapsibleOnDesktop(! $hasTopNavigation);

        if (Filament::getPanel('company')->hasTopNavigation()) {
            Filament::getPanel('company')->navigation(
                $this->buildCompanyNavigation()
            );
        } else {
            Filament::getPanel('company')->navigation(
                $this->buildCompanySidebarNavigation()
            );
        }

        NavigationGroup::configureUsing(static function (NavigationGroup $group): void {
            $group->localizeLabel();
        }, isImportant: true);
    }

    /**
     * Build the company navigation.
     */
    protected function buildCompanyNavigation(): callable
    {
        return function (NavigationBuilder $builder): NavigationBuilder {
            return $builder
                ->items(Dashboard::getNavigationItems())
                ->groups([
                    $this->buildSettingsGroup(),
                    $this->buildResourcesGroup(),
                ]);
        };
    }

    /**
     * Build the settings group.
     */
    protected function buildSettingsGroup(): NavigationGroup
    {
        return NavigationGroup::make('Settings')
            ->items([
                ...CurrencyResource::getNavigationItems(),
                ...DiscountResource::getNavigationItems(),
                ...TaxResource::getNavigationItems(),
                ...Appearance::getNavigationItems(),
                ...CompanyDefault::getNavigationItems(),
                ...Invoice::getNavigationItems(),
                ...CompanyProfile::getNavigationItems(),
                ...Localization::getNavigationItems(),
            ]);
    }

    /**
     * Build the resources group.
     */
    protected function buildResourcesGroup(): NavigationGroup
    {
        return NavigationGroup::make('Resources')
            ->items([
                ...AccountResource::getNavigationItems(),
                ...ConnectedAccount::getNavigationItems(),
                ...LiveCurrency::getNavigationItems(),
                ...DepartmentResource::getNavigationItems(),
            ]);
    }

    /**
     * Build the company sidebar navigation.
     */
    protected function buildCompanySidebarNavigation(): callable
    {
        return static function (NavigationBuilder $builder): NavigationBuilder {
            return $builder
                ->items(Dashboard::getNavigationItems())
                ->groups([
                    NavigationGroup::make('Accounting')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->extraSidebarAttributes(['class' => 'es-sidebar-group'])
                        ->items([
                            ...AccountChart::getNavigationItems(),
                            ...TransactionResource::getNavigationItems(),
                        ]),
                    NavigationGroup::make('Banking')
                        ->icon('heroicon-o-building-library')
                        ->items(AccountResource::getNavigationItems()),
                    NavigationGroup::make('HR')
                        ->icon('heroicon-o-user-group')
                        ->items(DepartmentResource::getNavigationItems()),
                    NavigationGroup::make('Services')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->items([
                            ...ConnectedAccount::getNavigationItems(),
                            ...LiveCurrency::getNavigationItems(),
                        ]),
                    NavigationGroup::make('Settings')
                        ->icon('heroicon-o-cog-8-tooth')
                        ->items([
                            ...Appearance::getNavigationItems(),
                            ...CompanyProfile::getNavigationItems(),
                            ...CurrencyResource::getNavigationItems(),
                            ...CompanyDefault::getNavigationItems(),
                            ...DiscountResource::getNavigationItems(),
                            ...Invoice::getNavigationItems(),
                            ...Localization::getNavigationItems(),
                            ...TaxResource::getNavigationItems(),
                        ]),
                ])
                ->items(Reports::getNavigationItems());
        };
    }
}
