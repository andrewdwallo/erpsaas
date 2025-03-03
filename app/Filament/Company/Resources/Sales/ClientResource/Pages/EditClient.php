<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\Pages;

use App\Concerns\RedirectToListPage;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Models\Common\Client;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;

class EditClient extends EditRecord
{
    use RedirectToListPage;

    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::FiveExtraLarge;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Client $record */
        $record = parent::handleRecordUpdate($record, $data);

        // Update billing address
        $billingAddress = $record->billingAddress;
        $billingAddress->update($data['billingAddress']);

        // Update shipping address
        $shippingAddress = $record->shippingAddress;
        $shippingData = $data['shippingAddress'];

        $shippingUpdateData = [
            'recipient' => $shippingData['recipient'],
            'phone' => $shippingData['phone'],
            'notes' => $shippingData['notes'],
        ];

        if ($shippingData['same_as_billing']) {
            $shippingUpdateData = [
                ...$shippingUpdateData,
                'parent_address_id' => $billingAddress->id,
                'address_line_1' => $billingAddress->address_line_1,
                'address_line_2' => $billingAddress->address_line_2,
                'country_code' => $billingAddress->country_code,
                'state_id' => $billingAddress->state_id,
                'city' => $billingAddress->city,
                'postal_code' => $billingAddress->postal_code,
            ];
        } else {
            $shippingUpdateData = [
                ...$shippingUpdateData,
                'parent_address_id' => null,
                'address_line_1' => $shippingData['address_line_1'],
                'address_line_2' => $shippingData['address_line_2'],
                'country_code' => $shippingData['country_code'],
                'state_id' => $shippingData['state_id'],
                'city' => $shippingData['city'],
                'postal_code' => $shippingData['postal_code'],
            ];
        }

        $shippingAddress->update($shippingUpdateData);

        return $record;
    }
}
