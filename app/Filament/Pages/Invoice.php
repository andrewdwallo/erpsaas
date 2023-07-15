<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Invoice extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Invoice';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'Invoice';

    protected static ?string $slug = 'invoice';

    protected static string $view = 'filament.pages.invoice';
}
