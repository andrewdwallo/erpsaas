<?php

namespace App\Filament\User\Clusters;

use Filament\Clusters\Cluster;

class Account extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-s-user';

    protected static ?string $navigationLabel = 'My Account';

    protected static ?string $clusterBreadcrumb = 'My Account';

    public static function getNavigationUrl(): string
    {
        return static::getUrl(panel: 'user');
    }
}
