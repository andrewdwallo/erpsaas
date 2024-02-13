<?php

namespace App\Observers;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Enums\AccountStatus;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Banking\BankAccount;
use App\Utilities\Accounting\AccountCode;

class AccountObserver
{
    public function creating(Account $account): void
    {
        $this->setCategoryAndType($account, true);

        // $bankAccount = $account->accountable;
        if (($account->accountable_type === BankAccount::class) && $account->code === null) {
            $this->setFieldsForBankAccount($account);
        }
    }

    public function updating(Account $account): void
    {
        if ($account->isDirty('subtype_id')) {
            $this->setCategoryAndType($account, false);
        }
    }

    private function setCategoryAndType(Account $account, bool $isCreating): void
    {
        $subtype = $account->subtype_id ? AccountSubtype::find($account->subtype_id) : null;

        if ($subtype) {
            $account->category = $subtype->category;
            $account->type = $subtype->type;
        } elseif ($isCreating) {
            $account->category = AccountCategory::Asset;
            $account->type = AccountType::CurrentAsset;
        }
    }

    private function setFieldsForBankAccount(Account $account): void
    {
        $generatedAccountCode = AccountCode::generate($account->company_id, $account->subtype_id);

        $account->code = $generatedAccountCode;
    }

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        //$account->histories()->create([
           // 'company_id' => $account->company_id,
          //  'account_id' => $account->id,
           // 'type' => $account->type,
          //  'name' => $account->name,
          //  'number' => $account->number,
           // 'currency_code' => $account->currency_code,
         //   'opening_balance' => $account->opening_balance,
         //   'balance' => $account->balance,
         //   'exchange_rate' => $account->currency->rate,
          //  'status' => AccountStatus::Open,
          //  'actions' => ['account_created'],
         //   'enabled' => $account->enabled,
          //  'changed_by' => $account->created_by,
        //]);
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(Account $account): void
    {
        //$actionsTaken = [];

        //foreach ($this->actions as $action => $attribute) {
            //if ($account->isDirty($attribute)) {
                //$actionsTaken[] = $action;
           // }
        //}

        //if (count($actionsTaken) > 0) {
            //$account->histories()->create([
                //'company_id' => $account->company_id,
               // 'account_id' => $account->id,
               // 'type' => $account->getOriginal('type'),
               // 'name' => $account->getOriginal('name'),
               // 'number' => $account->getOriginal('number'),
               // 'currency_code' => $account->getOriginal('currency_code'),
               // 'opening_balance' => $account->getRawOriginal('opening_balance'),
               // 'balance' => $account->getRawOriginal('balance'),
               // 'exchange_rate' => $account->currency->getRawOriginal('rate'),
               // 'status' => $account->getOriginal('status'),
               // 'actions' => $actionsTaken,
               // 'enabled' => $account->getOriginal('enabled'),
               // 'changed_by' => $account->updated_by,
            //]);
        //}
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

    private function getDefaultChartForBankAccount(Account $account): Account
    {
        $defaultChartCategory = AccountCategory::Asset;
        $defaultChartType = AccountType::CurrentAsset;

        //if ($account->type->isCreditCard()) {
            //$defaultChartCategory = ChartCategory::Liability;
            //$defaultChartType = ChartType::CurrentLiability;
        //}

        $subTypeId = $this->getSubTypeId($account->company_id, $defaultChartType);

        $latestChartCode = Account::where('company_id', $account->company_id)
            ->where('category', $defaultChartCategory)
            ->where('type', $defaultChartType)
            ->max('code');

        $newChartCode = $latestChartCode ? ++$latestChartCode : '1000';

        return Account::create([
            'company_id' => $account->company_id,
            'category' => $defaultChartCategory,
            'type' => $defaultChartType,
            'subtype_id' => $subTypeId,
            'code' => $newChartCode,
            'name' => $account->name,
            'currency_code' => $account->currency_code,
            'description' => $account->description ?? $account->name,
            'balance' => 0,
            'active' => true,
            'default' => false,
            'created_by' => $account->created_by,
            'updated_by' => $account->updated_by,
        ]);
    }

    private function getSubTypeId(int $companyId, AccountType $type): ?int
    {
        $subType = AccountSubtype::where('company_id', $companyId)
            ->where('name', 'Cash and Cash Equivalents')
            ->where('type', $type)
            ->first();

        if (!$subType) {
            $subType = AccountSubtype::where('company_id', $companyId)
                ->where('type', $type)
                ->first();
        }

        return $subType?->id;
    }

    private function updateChartBalance(Account $chart, mixed $amount): void
    {
        //$chart->balance += $amount;
        //$chart->save();
    }
}
