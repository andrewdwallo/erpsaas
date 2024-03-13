<?php

namespace App\Listeners;

use App\Events\CompanyDefaultUpdated;
use App\Models\Setting\CompanyDefault;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SyncAssociatedModels
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
    public function handle(CompanyDefaultUpdated $event): void
    {
        DB::transaction(function () use ($event) {
            $this->syncAssociatedModels($event);
        }, 5);
    }

    private function syncAssociatedModels(CompanyDefaultUpdated $event): void
    {
        /** @var CompanyDefault $record */
        $record = $event->record;
        $data = $event->data;

        $record_array = array_map('strval', $record->toArray());
        $data = array_map('strval', $data);

        $diff = array_diff_assoc($data, $record_array);

        $keyToMethodMap = [
            'bank_account_id' => 'bankAccount',
            'currency_code' => 'currency',
            'sales_tax_id' => 'salesTax',
            'purchase_tax_id' => 'purchaseTax',
            'sales_discount_id' => 'salesDiscount',
            'purchase_discount_id' => 'purchaseDiscount',
        ];

        foreach ($diff as $key => $value) {
            if (array_key_exists($key, $keyToMethodMap)) {
                $method = $keyToMethodMap[$key];
                $this->updateEnabledStatus($record->$method(), $value);
            }
        }
    }

    private function updateEnabledStatus(BelongsTo $relation, $newValue): void
    {
        if ($relation->exists()) {
            $previousDefault = $relation->getResults();
            $previousDefault->update(['enabled' => false]);
        }

        if ($newValue !== null) {
            $newDefault = $relation->getRelated()->newQuery()->where($relation->getOwnerKeyName(), $newValue)->first();
            $newDefault?->update(['enabled' => true]);
        }
    }
}
