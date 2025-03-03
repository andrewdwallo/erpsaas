<?php

namespace App\Filament\Forms\Components;

use CodeWithDennis\SimpleAlert\Components\Forms\SimpleAlert;

class Banner extends SimpleAlert
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->border();
    }

    public function danger(): static
    {
        $this->color = 'danger';
        $this->icon = 'heroicon-o-x-circle';

        return $this;
    }

    public function info(): static
    {
        $this->color = 'info';
        $this->icon = 'heroicon-o-information-circle';

        return $this;
    }

    public function success(): static
    {
        $this->color = 'success';
        $this->icon = 'heroicon-o-check-circle';

        return $this;
    }

    public function warning(): static
    {
        $this->color = 'warning';
        $this->icon = 'heroicon-o-exclamation-triangle';

        return $this;
    }
}
