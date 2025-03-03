<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\Pages;

use App\Concerns\HasTabSpecificColumnToggles;
use App\Enums\Accounting\InvoiceStatus;
use App\Filament\Company\Resources\Sales\InvoiceResource;
use App\Filament\Company\Resources\Sales\InvoiceResource\Widgets;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages\ViewRecurringInvoice;
use App\Models\Accounting\RecurringInvoice;
use CodeWithDennis\SimpleAlert\Components\Infolists\SimpleAlert;
use Filament\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;

class ListInvoices extends ListRecords
{
    use ExposesTableToWidgets;
    use HasTabSpecificColumnToggles;

    protected static string $resource = InvoiceResource::class;

    #[Url(except: '')]
    public string $recurringInvoice = '';

    protected static string $view = 'filament.company.resources.sales.invoice-resource.pages.list-invoices';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                SimpleAlert::make('recurringInvoiceFilter')
                    ->info()
                    ->title(function () {
                        if (empty($this->recurringInvoice)) {
                            return null;
                        }

                        $recurringInvoice = RecurringInvoice::find($this->recurringInvoice);

                        $clientName = $recurringInvoice?->client?->name;

                        if (! $clientName) {
                            return 'You are currently viewing invoices created from a recurring invoice';
                        }

                        $recurringInvoiceUrl = ViewRecurringInvoice::getUrl([
                            'record' => $recurringInvoice,
                        ]);

                        $link = Blade::render('filament::components.link', [
                            'href' => $recurringInvoiceUrl,
                            'slot' => 'a recurring invoice for ' . $clientName,
                        ]);

                        return new HtmlString(
                            "You are currently viewing invoices created from {$link}"
                        );
                    })
                    ->visible(fn () => ! empty($this->recurringInvoice))
                    ->actions([
                        Action::make('clearFilter')
                            ->label('Clear filter')
                            ->button()
                            ->outlined()
                            ->action('clearFilter'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\InvoiceOverview::make(),
        ];
    }

    public function clearFilter(): void
    {
        $this->recurringInvoice = '';
        $this->tableFilters = []; // Refresh widgets/table
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('All'),

            'unpaid' => Tab::make()
                ->label('Unpaid')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->unpaid();
                }),

            'draft' => Tab::make()
                ->label('Draft')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('status', InvoiceStatus::Draft);
                }),
        ];
    }
}
