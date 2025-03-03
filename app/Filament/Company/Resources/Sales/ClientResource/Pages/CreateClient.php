<?php

namespace App\Filament\Company\Resources\Sales\ClientResource\Pages;

use App\Concerns\RedirectToListPage;
use App\Enums\Common\AddressType;
use App\Filament\Company\Resources\Sales\ClientResource;
use App\Models\Common\Client;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;

class CreateClient extends CreateRecord
{
    use RedirectToListPage;

    protected static string $resource = ClientResource::class;

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return MaxWidth::FiveExtraLarge;
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var Client $record */
        $record = parent::handleRecordCreation($data);

        // Create billing address first
        $billingAddress = $record->addresses()->create([
            ...$data['billingAddress'],
            'type' => AddressType::Billing,
        ]);

        // Create shipping address with reference to billing if needed
        $shippingData = $data['shippingAddress'];

        $shippingAddress = [
            'type' => AddressType::Shipping,
            'recipient' => $shippingData['recipient'],
            'phone' => $shippingData['phone'],
            'notes' => $shippingData['notes'],
        ];

        if ($shippingData['same_as_billing']) {
            $shippingAddress = [
                ...$shippingAddress,
                'parent_address_id' => $billingAddress->id,
                'address_line_1' => $billingAddress->address_line_1,
                'address_line_2' => $billingAddress->address_line_2,
                'country_code' => $billingAddress->country_code,
                'state_id' => $billingAddress->state_id,
                'city' => $billingAddress->city,
                'postal_code' => $billingAddress->postal_code,
            ];
        } else {
            $shippingAddress = [
                ...$shippingAddress,
                'address_line_1' => $shippingData['address_line_1'],
                'address_line_2' => $shippingData['address_line_2'],
                'country_code' => $shippingData['country_code'],
                'state_id' => $shippingData['state_id'],
                'city' => $shippingData['city'],
                'postal_code' => $shippingData['postal_code'],
            ];
        }

        $record->addresses()->create($shippingAddress);

        return $record;
    }
}
