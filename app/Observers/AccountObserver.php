<?php

namespace App\Observers;

use App\Enums\AccountStatus;
use App\Models\Banking\Account;

class AccountObserver
{
    protected array $actions = [
        'exchange_rate_changed' => 'balance',
        'currency_changed' => 'currency_code',
        'status_changed' => 'status',
        'default_account_changed' => 'enabled',
        'type_changed' => 'type',
        'name_changed' => 'name',
        'number_changed' => 'number',
    ];

    public function creating(Account $account): void
    {
        $account->balance = $account->opening_balance;
    }

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        $account->histories()->create([
            'company_id' => $account->company_id,
            'account_id' => $account->id,
            'type' => $account->type,
            'name' => $account->name,
            'number' => $account->number,
            'currency_code' => $account->currency_code,
            'opening_balance' => $account->opening_balance,
            'balance' => $account->balance,
            'exchange_rate' => $account->currency->rate,
            'status' => AccountStatus::Open,
            'actions' => ['account_created'],
            'enabled' => $account->enabled,
            'changed_by' => $account->created_by,
        ]);
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(Account $account): void
    {
        $actionsTaken = [];

        foreach ($this->actions as $action => $attribute) {
            if ($account->isDirty($attribute)) {
                $actionsTaken[] = $action;
            }
        }

        if (count($actionsTaken) > 0) {
            $account->histories()->create([
                'company_id' => $account->company_id,
                'account_id' => $account->id,
                'type' => $account->getOriginal('type'),
                'name' => $account->getOriginal('name'),
                'number' => $account->getOriginal('number'),
                'currency_code' => $account->getOriginal('currency_code'),
                'opening_balance' => $account->getRawOriginal('opening_balance'),
                'balance' => $account->getRawOriginal('balance'),
                'exchange_rate' => $account->currency->getRawOriginal('rate'),
                'status' => $account->getOriginal('status'),
                'actions' => $actionsTaken,
                'enabled' => $account->getOriginal('enabled'),
                'changed_by' => $account->updated_by,
            ]);
        }
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "restored" event.
     */
    public function restored(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "force deleted" event.
     */
    public function forceDeleted(Account $account): void
    {
        //
    }
}
