<?php

namespace App\Filament\Company\Resources\Purchases\Vendors\Pages;

use App\Filament\Company\Resources\Purchases\Vendors\VendorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

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
