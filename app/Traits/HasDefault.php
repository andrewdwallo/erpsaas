<?php

namespace App\Traits;

trait HasDefault
{
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    public function isDisabled(): bool
    {
        return $this->enabled === false;
    }

    public static function enabledLabel(): string
    {
        return translate('Yes');
    }

    public static function disabledLabel(): string
    {
        return translate('No');
    }
}
