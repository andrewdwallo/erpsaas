<?php

namespace App\Faker;

use App\Models\Locale\State as StateModel;
use Faker\Provider\Base;

class State extends Base
{
    public function state(string $countryCode, string $column = 'id'): mixed
    {
        return StateModel::where('country_id', $countryCode)->inRandomOrder()->first()?->{$column};
    }
}
