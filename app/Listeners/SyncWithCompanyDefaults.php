<?php

namespace App\Listeners;

use App\Enums\{CategoryType, DiscountType, TaxType};
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
        $type = $model->getAttribute('type');

        $default = CompanyDefault::firstOrNew([
            'company_id' => $companyId,
        ]);

        match ($modelName) {
            'Discount' => $this->handleDiscount($default, $type, $model->getKey()),
            'Tax' => $this->handleTax($default, $type, $model->getKey()),
            'Category' => $this->handleCategory($default, $type, $model->getKey()),
            'Currency' => $default->currency_code = $model->getAttribute('code'),
            'Account' => $default->account_id = $model->getKey(),
            default => null,
        };

        $default->save();
    }

    private function handleDiscount($default, $type, $key): void
    {
        match (true) {
            $type === DiscountType::Sales => $default->sales_discount_id = $key,
            $type === DiscountType::Purchase => $default->purchase_discount_id = $key,
        };
    }

    private function handleTax($default, $type, $key): void
    {
        match (true) {
            $type === TaxType::Sales => $default->sales_tax_id = $key,
            $type === TaxType::Purchase => $default->purchase_tax_id = $key,
        };
    }

    private function handleCategory($default, $type, $key): void
    {
        match (true) {
            $type === CategoryType::Income => $default->income_category_id = $key,
            $type === CategoryType::Expense => $default->expense_category_id = $key,
        };
    }
}
