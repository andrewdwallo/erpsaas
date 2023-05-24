<?php

namespace App\Filament\Resources\AccountResource\Pages;

use App\Filament\Resources\AccountResource;
use App\Models\Banking\Account;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
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
        $data['company_id'] = auth()->user()->currentCompany->id;
        return $data;
    }

    protected function handleRecordUpdate(Model|Account $record, array $data): Model|Account
    {
        $currentCompanyId = auth()->user()->currentCompany->id;
        $accountId = $record->id;
        $enabledAccountsCount = Account::where('company_id', $currentCompanyId)
            ->where('enabled', true)
            ->where('id', '!=', $accountId)
            ->count();

        if ($data['enabled'] === true && $enabledAccountsCount > 0) {
            $this->disableOtherAccounts($currentCompanyId, $accountId);
        } elseif ($data['enabled'] === false && $enabledAccountsCount < 1) {
            $data['enabled'] = true;
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function disableOtherAccounts($companyId, $accountId): void
    {
        DB::table('accounts')
            ->where('company_id', $companyId)
            ->where('id', '!=', $accountId)
            ->update(['enabled' => false]);
    }
}
