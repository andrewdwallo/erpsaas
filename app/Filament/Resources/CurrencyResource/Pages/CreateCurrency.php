<?php

namespace App\Filament\Resources\CurrencyResource\Pages;

use App\Filament\Resources\CurrencyResource;
use App\Models\Setting\Currency;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
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
        $existingEnabledRecord = Currency::where('company_id', $companyId)
            ->where('enabled', true)
            ->first();

        if ($existingEnabledRecord !== null) {
            $existingEnabledRecord->enabled = false;
            $existingEnabledRecord->save();
        }
    }

    protected function ensureAtLeastOneEnabled($companyId, &$enabled): void
    {
        $enabledAccountsCount = Currency::where('company_id', $companyId)
            ->where('enabled', true)
            ->count();

        if ($enabledAccountsCount === 0) {
            $enabled = true;
        }
    }
}
