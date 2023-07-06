<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Banking\Account;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = Auth::user()->currentCompany->id;
        $data['enabled'] = (bool)$data['enabled'];
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $currentCompanyId = auth()->user()->currentCompany->id;

            $enabled = (bool)($data['enabled'] ?? false); // Ensure $enabled is always a boolean

            if ($enabled === true) {
                $this->disableExistingRecord($currentCompanyId);
            } else {
                $this->ensureAtLeastOneEnabled($currentCompanyId, $enabled);
            }

            $data['enabled'] = $enabled;

            return parent::handleRecordCreation($data);
        });
    }

    protected function disableExistingRecord($companyId): void
    {
        $existingEnabledRecord = Account::where('company_id', $companyId)
            ->where('enabled', true)
            ->first();

        if ($existingEnabledRecord !== null) {
            $existingEnabledRecord->enabled = false;
            $existingEnabledRecord->save();
            $this->defaultAccountChanged();
        }
    }

    protected function ensureAtLeastOneEnabled($companyId, &$enabled): void
    {
        $enabledAccountsCount = Account::where('company_id', $companyId)
            ->where('enabled', true)
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
