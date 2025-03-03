<?php

namespace App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages;

use App\Concerns\HasTabSpecificColumnToggles;
use App\Enums\Accounting\RecurringInvoiceStatus;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;

class ListRecurringInvoices extends ListRecords
{
    use HasTabSpecificColumnToggles;

    protected static string $resource = RecurringInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
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

            'active' => Tab::make()
                ->label('Active')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('status', RecurringInvoiceStatus::Active);
                }),

            'draft' => Tab::make()
                ->label('Draft')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('status', RecurringInvoiceStatus::Draft);
                }),
        ];
    }
}
