<?php

namespace App\Concerns;

trait RedirectToListPage
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
