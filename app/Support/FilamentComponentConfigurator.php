<?php

namespace App\Support;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Support\Enums\Alignment;

class FilamentComponentConfigurator
{
    public static function configureActionModals(Action $action): void
    {
        $action
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalFooterActionsAlignment(Alignment::End);

        if ($action instanceof CreateAction || $action instanceof CreateAction) {
            $action->createAnother(false);
        }
    }

    public static function configureDeleteAction(Action $action): void
    {
        $action->databaseTransaction();
    }
}
