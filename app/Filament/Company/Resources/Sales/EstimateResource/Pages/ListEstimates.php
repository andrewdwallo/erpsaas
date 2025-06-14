<?php

namespace App\Filament\Company\Resources\Sales\EstimateResource\Pages;

use App\Enums\Accounting\EstimateStatus;
use App\Filament\Company\Resources\Sales\EstimateResource;
use App\Filament\Company\Resources\Sales\EstimateResource\Widgets\EstimateOverview;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;

class ListEstimates extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = EstimateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EstimateOverview::make(),
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

            'active' => Tab::make()
                ->label('Active')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->active();
                }),

            'draft' => Tab::make()
                ->label('Draft')
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('status', EstimateStatus::Draft);
                }),
        ];
    }
}
