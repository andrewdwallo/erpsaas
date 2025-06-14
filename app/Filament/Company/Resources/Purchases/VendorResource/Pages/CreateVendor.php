<?php

namespace App\Filament\Company\Resources\Purchases\VendorResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Purchases\VendorResource;
use App\Models\Common\Vendor;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateVendor extends CreateRecord
{
    use HandlePageRedirect;

    protected static string $resource = VendorResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return Vendor::createWithRelations($data);
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::FiveExtraLarge;
    }
}
