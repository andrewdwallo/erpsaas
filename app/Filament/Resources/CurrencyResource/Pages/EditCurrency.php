<?php

namespace App\Filament\Resources\CurrencyResource\Pages;

use App\Filament\Resources\CurrencyResource;
use App\Models\Setting\Currency;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditCurrency extends EditRecord
{
    protected static string $resource = CurrencyResource::class;

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

    protected function handleRecordUpdate(Model|Currency $record, array $data): Model|Currency
    {
        $currentCompanyId = auth()->user()->currentCompany->id;
        $currencyId = $record->id;
        $enabledCurrenciesCount = Currency::where('company_id', $currentCompanyId)
            ->where('enabled', true)
            ->where('id', '!=', $currencyId)
            ->count();

        if ($data['enabled'] === true && $enabledCurrenciesCount > 0) {
            $this->disableOtherCurrencies($currentCompanyId, $currencyId);
        } elseif ($data['enabled'] === false && $enabledCurrenciesCount < 1) {
            $data['enabled'] = true;
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function disableOtherCurrencies($companyId, $currencyId): void
    {
        DB::table('currencies')
            ->where('company_id', $companyId)
            ->where('id', '!=', $currencyId)
            ->update(['enabled' => false]);
    }
}
