<?php

namespace App\Filament\User\Clusters\Account\Pages;

use App\Filament\User\Clusters\Account;
use Filament\Support\Enums\Width;
use Wallo\FilamentCompanies\Pages\User\PersonalAccessTokens as BasePersonalAccessTokens;

class PersonalAccessTokens extends BasePersonalAccessTokens
{
    protected static ?string $cluster = Account::class;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 20;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::ScreenTwoExtraLarge;
    }
}
