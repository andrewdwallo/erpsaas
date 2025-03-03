<?php

namespace App\Services;

use App\Enums\Accounting\AccountType;
use App\Enums\Banking\BankAccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountSubtype;
use App\Models\Accounting\Adjustment;
use App\Models\Banking\BankAccount;
use App\Models\Company;
use App\Utilities\Currency\CurrencyAccessor;
use Exception;

class ChartOfAccountsService
{
    public function createChartOfAccounts(Company $company): void
    {
        $chartOfAccounts = config('chart-of-accounts.default');

        // Always create a non-recoverable "Purchase Tax" adjustment, even without an account
        $this->createAdjustmentForAccount($company, 'tax', 'purchase', false);

        foreach ($chartOfAccounts as $type => $subtypes) {
            foreach ($subtypes as $subtypeName => $subtypeConfig) {
                $subtype = $company->accountSubtypes()
                    ->createQuietly([
                        'multi_currency' => $subtypeConfig['multi_currency'] ?? false,
                        'inverse_cash_flow' => $subtypeConfig['inverse_cash_flow'] ?? false,
                        'category' => AccountType::from($type)->getCategory()->value,
                        'type' => $type,
                        'name' => $subtypeName,
                        'description' => $subtypeConfig['description'] ?? 'No description available.',
                    ]);

                try {
                    $this->createDefaultAccounts($company, $subtype, $subtypeConfig);
                } catch (Exception $e) {
                    // Log the error
                    logger()->alert('Failed to create a company with its defaults, blocking critical business functionality.', [
                        'error' => $e->getMessage(),
                        'userId' => $company->owner->id,
                        'companyId' => $company->id,
                    ]);

                    throw $e;
                }
            }
        }
    }

    private function createDefaultAccounts(Company $company, AccountSubtype $subtype, array $subtypeConfig): void
    {
        if (isset($subtypeConfig['accounts']) && is_array($subtypeConfig['accounts'])) {
            $baseCode = $subtypeConfig['base_code'];
            $defaultCurrencyCode = CurrencyAccessor::getDefaultCurrency();

            if (empty($defaultCurrencyCode)) {
                throw new Exception('No default currency available for creating accounts.');
            }

            foreach ($subtypeConfig['accounts'] as $accountName => $accountDetails) {
                // Create the Account without directly setting bank_account_id
                /** @var Account $account */
                $account = $company->accounts()->createQuietly([
                    'subtype_id' => $subtype->id,
                    'category' => $subtype->type->getCategory()->value,
                    'type' => $subtype->type->value,
                    'code' => $baseCode++,
                    'name' => $accountName,
                    'currency_code' => $defaultCurrencyCode,
                    'description' => $accountDetails['description'] ?? 'No description available.',
                    'default' => true,
                    'created_by' => $company->owner->id,
                    'updated_by' => $company->owner->id,
                ]);

                // Check if we need to create a BankAccount for this Account
                if ($subtypeConfig['multi_currency'] && isset($subtypeConfig['bank_account_type'])) {
                    $bankAccount = $this->createBankAccountForMultiCurrency($company, $subtypeConfig['bank_account_type']);

                    // Associate the BankAccount with the Account
                    $bankAccount->account()->associate($account);
                    $bankAccount->saveQuietly();
                }

                if (isset($subtypeConfig['adjustment_category'], $subtypeConfig['adjustment_type'], $subtypeConfig['adjustment_recoverable'])) {
                    $adjustment = $this->createAdjustmentForAccount($company, $subtypeConfig['adjustment_category'], $subtypeConfig['adjustment_type'], $subtypeConfig['adjustment_recoverable']);

                    // Associate the Adjustment with the Account
                    $adjustment->account()->associate($account);

                    $adjustment->name = $account->name;

                    $adjustment->description = $account->description;

                    $adjustment->saveQuietly();
                }
            }
        }
    }

    private function createBankAccountForMultiCurrency(Company $company, string $bankAccountType): BankAccount
    {
        $noDefaultBankAccount = $company->bankAccounts()->where('enabled', true)->doesntExist();

        return $company->bankAccounts()->createQuietly([
            'type' => BankAccountType::from($bankAccountType) ?? BankAccountType::Other,
            'enabled' => $noDefaultBankAccount,
            'created_by' => $company->owner->id,
            'updated_by' => $company->owner->id,
        ]);
    }

    private function createAdjustmentForAccount(Company $company, string $category, string $type, bool $recoverable): Adjustment
    {
        $defaultRate = match ([$category, $type]) {
            ['tax', 'sales'], ['tax', 'purchase'] => '8',
            ['discount', 'sales'], ['discount', 'purchase'] => '5',
            default => '0',
        };

        if ($category === 'tax' && $type === 'purchase' && $recoverable === false) {
            $name = 'Purchase Tax';
            $description = 'This tax is non-recoverable and is included as part of the total cost of the purchase. The tax amount is embedded into the associated expense or asset account based on the type of purchase.';
        }

        return $company->adjustments()->createQuietly([
            'name' => $name ?? null,
            'description' => $description ?? null,
            'category' => $category,
            'type' => $type,
            'recoverable' => $recoverable,
            'rate' => $defaultRate,
            'computation' => 'percentage',
            'created_by' => $company->owner->id,
            'updated_by' => $company->owner->id,
        ]);
    }
}
