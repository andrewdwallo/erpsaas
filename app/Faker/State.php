<?php

namespace App\Faker;

use App\Models\Locale\State as StateModel;
use Faker\Provider\Base;

class State extends Base
{
    public function state(string $countryCode, string $column = 'id'): mixed
    {
        $state = StateModel::where('country_code', $countryCode)->inRandomOrder()->first();

        return $state?->{$column};
    }
}
