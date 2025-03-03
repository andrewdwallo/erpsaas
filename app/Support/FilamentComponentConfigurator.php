<?php

namespace App\Support;

use Filament\Actions\CreateAction;
use Filament\Actions\MountableAction;
use Filament\Support\Enums\Alignment;

class FilamentComponentConfigurator
{
    public static function configureActionModals(MountableAction $action): void
    {
        $action
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalFooterActionsAlignment(Alignment::End);

        if ($action instanceof CreateAction || $action instanceof \Filament\Tables\Actions\CreateAction) {
            $action->createAnother(false);
        }
    }

    public static function configureDeleteAction(MountableAction $action): void
    {
        $action->databaseTransaction();
    }
}
