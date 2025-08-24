<?php

namespace App\Filament\Company\Resources\Common\Offerings\Pages;

use App\Filament\Company\Resources\Common\Offerings\OfferingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListOfferings extends ListRecords
{
    protected static string $resource = OfferingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return 'max-w-8xl';
    }
}
