<?php

namespace App\DTO;

use App\Models\Common\Client;

readonly class ClientDTO
{
    public function __construct(
        public string $name,
        public string $addressLine1,
        public string $addressLine2,
        public string $city,
        public string $state,
        public string $postalCode,
        public string $country,
    ) {}

    public static function fromModel(Client $client): self
    {
        $address = $client->billingAddress ?? null;

        return new self(
            name: $client->name,
            addressLine1: $address?->address_line_1 ?? '',
            addressLine2: $address?->address_line_2 ?? '',
            city: $address?->city ?? '',
            state: $address?->state?->name ?? '',
            postalCode: $address?->postal_code ?? '',
            country: $address?->country?->name ?? '',
        );
    }

    public function getFormattedAddressHtml(): ?string
    {
        if (empty($this->addressLine1)) {
            return null;
        }

        $lines = array_filter([
            $this->addressLine1,
            $this->addressLine2,
            implode(', ', array_filter([
                $this->city,
                $this->state,
                $this->postalCode,
            ])),
            $this->country,
        ]);

        return collect($lines)
            ->map(static fn ($line) => "<p>{$line}</p>")
            ->join('');
    }
}
