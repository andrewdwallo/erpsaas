<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Banking\Account;
use App\Models\Setting\Currency;
use App\Traits\HandlesResourceRecordUpdate;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditAccount extends EditRecord
{
    use HandlesResourceRecordUpdate;

    protected static string $resource = AccountResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['enabled'] = (bool)$data['enabled'];

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordUpdate(Model|Account $record, array $data): Model|Account
    {
        $user = Auth::user();

        if (!$user) {
            throw new Halt('No authenticated user found.');
        }

        $oldCurrency = $record->currency_code;
        $newCurrency = $data['currency_code'];

        if ($oldCurrency !== $newCurrency) {
            $data['opening_balance'] = Currency::convertBalance(
                $data['opening_balance'],
                $oldCurrency,
                $newCurrency
            );
        }

        $this->handleRecordUpdateWithUniqueField($record, $data, $user);

        return parent::handleRecordUpdate($record, $data);
    }
}
