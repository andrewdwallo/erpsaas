<?php

namespace App\Filament\Company\Resources\Purchases\VendorResource\Pages;

use App\Concerns\HandlePageRedirect;
use App\Filament\Company\Resources\Purchases\VendorResource;
use App\Models\Common\Vendor;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditVendor extends EditRecord
{
    use HandlePageRedirect;

    protected static string $resource = VendorResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Vendor $record */
        $record->updateWithRelations($data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::FiveExtraLarge;
    }
}
