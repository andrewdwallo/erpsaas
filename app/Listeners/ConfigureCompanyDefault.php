<?php

namespace App\Listeners;

use App\Enums\DateFormat;
use App\Enums\Font;
use App\Enums\MaxContentWidth;
use App\Enums\ModalWidth;
use App\Enums\PrimaryColor;
use App\Enums\RecordsPerPage;
use App\Enums\TableSortDirection;
use App\Enums\WeekStart;
use App\Events\CompanyConfigured;
use App\Utilities\Currency\ConfigureCurrencies;
use Filament\Actions\MountableAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Components\Tab as ResourcesTab;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;

class ConfigureCompanyDefault
{
    /**
     * Handle the event.
     */
    public function handle(CompanyConfigured $event): void
    {
        $company = $event->company;
        $paginationPageOptions = RecordsPerPage::caseValues();
        $defaultPaginationPageOption = $company->appearance->records_per_page->value ?? RecordsPerPage::DEFAULT;
        $defaultSort = $company->appearance->table_sort_direction->value ?? TableSortDirection::DEFAULT;
        $stripedTables = $company->appearance->is_table_striped ?? false;
        $defaultPrimaryColor = $company->appearance->primary_color ?? PrimaryColor::from(PrimaryColor::DEFAULT);
        $modalWidth = $company->appearance->modal_width->value ?? ModalWidth::DEFAULT;
        $maxContentWidth = $company->appearance->max_content_width->value ?? MaxContentWidth::DEFAULT;
        $defaultFont = $company->appearance->font->value ?? Font::DEFAULT;
        $default_language = $company->locale->language ?? config('transmatic.source_locale');
        $defaultTimezone = $company->locale->timezone ?? config('app.timezone');
        $dateFormat = $company->locale->date_format->value ?? DateFormat::DEFAULT;
        $weekStart = $company->locale->week_start->value ?? WeekStart::DEFAULT;

        app()->setLocale($default_language);
        locale_set_default($default_language);
        config(['app.timezone' => $defaultTimezone]);
        date_default_timezone_set($defaultTimezone);

        Table::configureUsing(static function (Table $table) use ($paginationPageOptions, $defaultSort, $stripedTables, $defaultPaginationPageOption): void {

            $table
                ->paginationPageOptions($paginationPageOptions)
                ->defaultSort(column: 'id', direction: $defaultSort)
                ->striped($stripedTables)
                ->defaultPaginationPageOption($defaultPaginationPageOption);
        }, isImportant: true);

        MountableAction::configureUsing(static function (MountableAction $action) use ($modalWidth): void {
            $actionOperation = $action->getName();

            if (in_array($actionOperation, ['delete', 'restore', 'forceDelete', 'detach'])) {
                $action->modalWidth($modalWidth);
            }
        }, isImportant: true);

        FilamentColor::register([
            'primary' => $defaultPrimaryColor->getColor(),
        ]);

        Filament::getPanel('company')
            ->font($defaultFont)
            ->brandName($company->name)
            ->maxContentWidth($maxContentWidth);

        DatePicker::configureUsing(static function (DatePicker $component) use ($dateFormat, $weekStart) {
            $component
                ->displayFormat($dateFormat)
                ->firstDayOfWeek($weekStart);
        });

        Tab::configureUsing(static function (Tab $tab) {
            $label = $tab->getLabel();

            $tab->label(ucwords(translate($label)));
        }, isImportant: true);

        Section::configureUsing(static function (Section $section): void {
            $heading = $section->getHeading();
            $section->heading(ucfirst(translate($heading)));
        }, isImportant: true);

        ResourcesTab::configureUsing(static function (ResourcesTab $tab): void {
            $tab->localizeLabel();
        }, isImportant: true);

        ConfigureCurrencies::syncCurrencies();
    }
}
