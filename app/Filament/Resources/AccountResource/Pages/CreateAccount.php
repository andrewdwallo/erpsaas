<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Banking\Account;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateAccount extends CreateRecord
{
    protected static string $resource = AccountResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['company_id'] = auth()->user()->currentCompany->id;
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $currentCompanyId = auth()->user()->currentCompany->id;
        $accountId = $data['id'] ?? null;
        $enabledAccountsCount = Account::where('company_id', $currentCompanyId)
            ->where('enabled', true)
            ->where('id', '!=', $accountId)
            ->count();

        if ($data['enabled'] === true && $enabledAccountsCount > 0) {
            $this->disableOtherAccounts($currentCompanyId, $accountId);
        } elseif ($data['enabled'] === false && $enabledAccountsCount < 1) {
            $data['enabled'] = true;
        }

        return parent::handleRecordCreation($data);
    }

    protected function disableOtherAccounts($companyId, $accountId): void
    {
        DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('id', '!=', $accountId)
            ->update(['enabled' => false]);
    }
}
