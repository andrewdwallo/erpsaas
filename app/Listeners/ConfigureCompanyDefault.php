<?php

namespace App\Listeners;

use App\Events\CompanyConfigured;
use App\Services\CompanySettingsService;
use App\Utilities\Currency\ConfigureCurrencies;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Components\Tab as ResourcesTab;

class ConfigureCompanyDefault
{
    /**
     * Handle the event.
     */
    public function handle(CompanyConfigured $event): void
    {
        $company = $event->company;
        $companyId = $company->id;

        session(['current_company_id' => $companyId]);

        $settings = CompanySettingsService::getSettings($companyId);

        app()->setLocale($settings['default_language']);
        locale_set_default($settings['default_language']);
        config(['app.timezone' => $settings['default_timezone']]);
        date_default_timezone_set($settings['default_timezone']);

        Filament::getPanel('company')
            ->brandName($company->name);

        DatePicker::configureUsing(static function (DatePicker $component) use ($settings) {
            $component
                ->displayFormat($settings['default_date_format'])
                ->firstDayOfWeek($settings['default_week_start']);
        });

        Tab::configureUsing(static function (Tab $tab) {
            $label = $tab->getLabel();

            if ($label) {
                $translatedLabel = translate($label);

                $tab->label(ucwords($translatedLabel));
            }
        }, isImportant: true);

        Section::configureUsing(static function (Section $section): void {
            $heading = $section->getHeading();

            if ($heading) {
                $translatedHeading = translate($heading);

                $section->heading(ucfirst($translatedHeading));
            }
        }, isImportant: true);

        ResourcesTab::configureUsing(static function (ResourcesTab $tab): void {
            $tab->localizeLabel();
        }, isImportant: true);

        ConfigureCurrencies::syncCurrencies();
    }
}
