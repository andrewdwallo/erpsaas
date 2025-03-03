<?php

namespace App\Observers;

use App\Enums\Accounting\AccountCategory;
use App\Enums\Accounting\AccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Utilities\Accounting\AccountCode;
use App\Utilities\Currency\CurrencyAccessor;

class AccountObserver
{
    public function creating(Account $account): void
    {
        $this->setCategoryAndType($account, true);
        $this->setCurrency($account);
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

    private function setCurrency(Account $account): void
    {
        if ($account->currency_code === null && $account->subtype->multi_currency === false) {
            $account->currency_code = CurrencyAccessor::getDefaultCurrency();
        }
    }

    private function setFieldsForBankAccount(Account $account): void
    {
        $generatedAccountCode = AccountCode::generate($account->subtype);

        $account->code = $generatedAccountCode;
    }

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        if ($account->bankAccount && $account->code === null) {
            $this->setFieldsForBankAccount($account);
            $account->save();
        }
    }
}
