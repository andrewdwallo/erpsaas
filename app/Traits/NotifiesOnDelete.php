<?php

namespace App\Traits;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait NotifiesOnDelete
{
    public static function notifyBeforeDelete(Model $record, string $reason): void
    {
        $reason = translate($reason);

        Notification::make()
            ->danger()
            ->title(translate('Action Denied'))
            ->body(translate(':Name cannot be deleted because it is :reason. Please update settings before deletion.', [
                'Name' => $record->getAttribute('name'),
                'reason' => $reason,
            ]))
            ->persistent()
            ->send();
    }

    public static function notifyBeforeDeleteMultiple(Collection $records, string $reason): void
    {
        $reason = translate($reason);

        $namesList = implode('<br>', array_map(static function ($record) {
            return '&bull; ' . $record->getAttribute('name');
        }, $records->all()));

        $message = translate('The following items cannot be deleted because they are :reason. Please update settings before deletion.', compact('reason')) . '<br><br>';

        $message .= $namesList;

        Notification::make()
            ->danger()
            ->title(translate('Action Denied'))
            ->body($message)
            ->persistent()
            ->send();
    }
}
