<?php

namespace App\Filament\Company\Clusters\Settings\Resources\Adjustments\Pages;

use App\Filament\Company\Clusters\Settings\Resources\Adjustments\AdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListAdjustments extends ListRecords
{
    protected static string $resource = AdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::ScreenTwoExtraLarge;
    }
}
