<?php

namespace App\Filament\Resources\CurrencyResource\Pages;

use App\Filament\Resources\CurrencyResource;
use App\Models\Setting\Currency;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
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
        $data['company_id'] = auth()->user()->currentCompany->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $currentCompanyId = auth()->user()->currentCompany->id;
        $currencyId = $data['id'] ?? null;
        $enabledCurrenciesCount = Currency::where('company_id', $currentCompanyId)
            ->where('enabled', '1')
            ->where('id', '!=', $currencyId)
            ->count();

        if ($data['enabled'] === '1' && $enabledCurrenciesCount > 0) {
            $this->disableOtherCurrencies($currentCompanyId, $currencyId);
        } elseif ($data['enabled'] === '0' && $enabledCurrenciesCount < 1) {
            $data['enabled'] = '1';
        }

        return parent::handleRecordCreation($data);
    }

    protected function disableOtherCurrencies($companyId, $currencyId): void
    {
        DB::table('currencies')
            ->where('company_id', $companyId)
            ->where('id', '!=', $currencyId)
            ->update(['enabled' => '0']);
    }
}
