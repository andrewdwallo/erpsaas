<?php

namespace App\Listeners;

use App\Enums\Font;
use App\Enums\MaxContentWidth;
use App\Enums\ModalWidth;
use App\Enums\PrimaryColor;
use App\Enums\RecordsPerPage;
use App\Enums\TableSortDirection;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\MountableAction;
use Filament\Events\TenantSet;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;

class ConfigureCompanyDefault
{
    /**
     * Handle the event.
     */
    public function handle(TenantSet $event): void
    {
        /** @var Company $company */
        $company = $event->getTenant();
        $paginationPageOptions = RecordsPerPage::caseValues();
        $defaultPaginationPageOption = $company->appearance->records_per_page->value ?? RecordsPerPage::DEFAULT;
        $defaultSort = $company->appearance->table_sort_direction->value ?? TableSortDirection::DEFAULT;
        $stripedTables = $company->appearance->is_table_striped ?? false;
        $defaultPrimaryColor = $company->appearance->primary_color ?? PrimaryColor::from(PrimaryColor::DEFAULT);
        $modalWidth = $company->appearance->modal_width->value ?? ModalWidth::DEFAULT;
        $maxContentWidth = $company->appearance->max_content_width->value ?? MaxContentWidth::DEFAULT;
        $defaultFont = $company->appearance->font->value ?? Font::DEFAULT;
        $hasTopNavigation = $company->appearance->has_top_navigation ?? false;

        Table::configureUsing(static function (Table $table) use ($paginationPageOptions, $defaultSort, $stripedTables, $defaultPaginationPageOption): void {
            $table
                ->paginationPageOptions($paginationPageOptions)
                ->defaultSort(column: 'id', direction: $defaultSort)
                ->striped($stripedTables)
                ->defaultPaginationPageOption($defaultPaginationPageOption);
        }, isImportant: true);

        MountableAction::configureUsing(static function (MountableAction $action) use ($modalWidth): void {
            $action->modalWidth($modalWidth);
        }, isImportant: true);

        $defaultColor = FilamentColor::register([
            'primary' => $defaultPrimaryColor->getColor(),
        ]);

        FilamentColor::swap($defaultColor);

        Filament::getDefaultPanel()
            ->font($defaultFont)
            ->brandName($company->name)
            ->topNavigation($hasTopNavigation)
            ->maxContentWidth($maxContentWidth);
    }
}
