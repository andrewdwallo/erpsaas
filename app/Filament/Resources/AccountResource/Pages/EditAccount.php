<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Banking\Account;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['company_id'] = Auth::user()->currentCompany->id;
        $data['enabled'] = (bool)$data['enabled'];
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function handleRecordUpdate(Model|Account $record, array $data): Model|Account
    {
        return DB::transaction(function () use ($record, $data) {
            $currentCompanyId = auth()->user()->currentCompany->id;
            $recordId = $record->id;
            $enabled = (bool)($data['enabled'] ?? false);

            // If the record is enabled, disable all other records for the same company
            if ($enabled === true) {
                $this->disableExistingRecord($currentCompanyId, $recordId);
            }
            // If the record is disabled, ensure at least one record remains enabled
            elseif ($enabled === false) {
                $this->ensureAtLeastOneEnabled($currentCompanyId, $recordId, $enabled);
            }

            $data['enabled'] = $enabled;

            return parent::handleRecordUpdate($record, $data);
        });
    }

    protected function disableExistingRecord(int $companyId, int $recordId): void
    {
        $existingEnabledAccount = Account::where('company_id', $companyId)
            ->where('enabled', true)
            ->where('id', '!=', $recordId)
            ->first();

        if ($existingEnabledAccount !== null) {
            $existingEnabledAccount->enabled = false;
            $existingEnabledAccount->save();
            $this->defaultAccountChanged();
        }
    }

    protected function ensureAtLeastOneEnabled(int $companyId, int $recordId, bool &$enabled): void
    {
        $enabledAccountsCount = Account::where('company_id', $companyId)
            ->where('enabled', true)
            ->where('id', '!=', $recordId)
            ->count();

        if ($enabledAccountsCount === 0) {
            $enabled = true;
        }
    }

    protected function defaultAccountChanged(): void
    {
        Notification::make()
            ->warning()
            ->title('Default account updated')
            ->body('Your default account has been updated. Please check your account settings to review this change and ensure it is correct.')
            ->persistent()
            ->send();
    }
}
