<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\Pages;

use App\Enums\Accounting\BillStatus;
use App\Filament\Company\Resources\Purchases\BillResource;
use App\Filament\Company\Resources\Purchases\BillResource\Widgets\BillOverview;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;

class ListBills extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = BillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('payBills')
                ->outlined()
                ->url(PayBills::getUrl()),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BillOverview::class,
        ];
    }

    public function getMaxContentWidth(): Width | string | null
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

            'paid' => Tab::make()
                ->label('Paid')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('status', BillStatus::Paid);
                }),
        ];
    }
}
