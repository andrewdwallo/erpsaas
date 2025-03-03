<?php

namespace App\DTO;

readonly class ClientPreviewDTO extends ClientDTO
{
    public static function fake(): self
    {
        return new self(
            name: 'John Doe',
            addressLine1: '1234 Elm St',
            addressLine2: 'Suite 123',
            city: 'Springfield',
            state: 'Illinois',
            postalCode: '62701',
            country: 'United States',
        );
    }
}
