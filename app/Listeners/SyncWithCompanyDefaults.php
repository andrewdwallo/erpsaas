<?php

namespace App\Listeners;

use App\Events\CompanyDefaultEvent;
use App\Models\Setting\CompanyDefault;
use Illuminate\Support\Facades\DB;

class SyncWithCompanyDefaults
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompanyDefaultEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $this->syncWithCompanyDefaults($event);
        }, 5);
    }

    private function syncWithCompanyDefaults($event): void
    {
        $model = $event->model;

        if (! $model->getAttribute('enabled') || ! auth()->check() || ! auth()->user()->currentCompany) {
            return;
        }

        $companyId = auth()->user()->currentCompany->id;

        if (! $companyId) {
            return;
        }

        $this->updateCompanyDefaults($model, $companyId);
    }

    private function updateCompanyDefaults($model, $companyId): void
    {
        $modelName = class_basename($model);

        $default = CompanyDefault::firstOrNew([
            'company_id' => $companyId,
        ]);

        match ($modelName) {
            'Currency' => $default->currency_code = $model->getAttribute('code'),
            'BankAccount' => $default->bank_account_id = $model->getKey(),
            default => null,
        };

        $default->save();
    }
}
