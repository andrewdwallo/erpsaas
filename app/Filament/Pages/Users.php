<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Wallo\FilamentCompanies\FilamentCompanies;

class Users extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.users';

    protected static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->currentCompany->id === 1;
    }

    public function mount(): void
    {
        abort_unless(Auth::user()->currentCompany->id === 1, 403);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\Users\Tables\Users::class,
        ];
    }

    protected static function getNavigationBadge(): ?string
    {
        return FilamentCompanies::userModel()::count();
    }
}
