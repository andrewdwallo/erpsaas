<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Companies extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-office-building';

    protected static string $view = 'filament.pages.companies';

    protected static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->currentCompany->name === 'ERPSAAS';
    }

    public function mount(): void
    {
        abort_unless(Auth::user()->currentCompany->name === 'ERPSAAS', 403);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Pages\Widgets\Companies::class,
        ];
    }
}
