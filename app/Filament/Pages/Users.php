<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Users extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.users';

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
            \App\Filament\Pages\Widgets\Users::class,
        ];
    }
}
