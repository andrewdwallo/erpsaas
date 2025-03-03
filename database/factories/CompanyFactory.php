<?php

namespace Database\Factories;

use App\Models\Accounting\Bill;
use App\Models\Accounting\Estimate;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\RecurringInvoice;
use App\Models\Accounting\Transaction;
use App\Models\Common\Client;
use App\Models\Common\Offering;
use App\Models\Common\Vendor;
use App\Models\Company;
use App\Models\Setting\CompanyProfile;
use App\Models\User;
use App\Services\CompanyDefaultService;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'user_id' => User::factory(),
            'personal_company' => true,
        ];
    }

    public function withCompanyProfile(): self
    {
        return $this->afterCreating(function (Company $company) {
            CompanyProfile::factory()->forCompany($company)->withAddress()->create();
        });
    }

    /**
     * Set up default settings for the company after creation.
     */
    public function withCompanyDefaults(): self
    {
        return $this->afterCreating(function (Company $company) {
            $countryCode = $company->profile->address->country_code;
            $companyDefaultService = app(CompanyDefaultService::class);
            $companyDefaultService->createCompanyDefaults($company, $company->owner, 'USD', $countryCode, 'en');
        });
    }

    public function withTransactions(int $count = 2000): self
    {
        return $this->afterCreating(function (Company $company) use ($count) {
            $defaultBankAccount = $company->default->bankAccount;

            Transaction::factory()
                ->forCompanyAndBankAccount($company, $defaultBankAccount)
                ->count($count)
                ->create([
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);
        });
    }

    public function withClients(int $count = 10): self
    {
        return $this->has(Client::factory()->count($count)->withPrimaryContact()->withAddresses());
    }

    public function withVendors(int $count = 10): self
    {
        return $this->has(Vendor::factory()->count($count)->withContact()->withAddress());
    }

    public function withOfferings(int $count = 10): self
    {
        return $this->afterCreating(function (Company $company) use ($count) {
            Offering::factory()
                ->count($count)
                ->sellable()
                ->withSalesAdjustments()
                ->purchasable()
                ->withPurchaseAdjustments()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);
        });
    }

    public function withInvoices(int $count = 10): self
    {
        return $this->afterCreating(function (Company $company) use ($count) {
            $draftCount = (int) floor($count * 0.2);
            $approvedCount = (int) floor($count * 0.2);
            $paidCount = (int) floor($count * 0.3);
            $partialCount = (int) floor($count * 0.1);
            $overpaidCount = (int) floor($count * 0.1);
            $overdueCount = $count - ($draftCount + $approvedCount + $paidCount + $partialCount + $overpaidCount);

            Invoice::factory()
                ->count($draftCount)
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Invoice::factory()
                ->count($approvedCount)
                ->approved()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Invoice::factory()
                ->count($paidCount)
                ->paid()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Invoice::factory()
                ->count($partialCount)
                ->partial()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Invoice::factory()
                ->count($overpaidCount)
                ->overpaid()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Invoice::factory()
                ->count($overdueCount)
                ->overdue()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);
        });
    }

    public function withRecurringInvoices(int $count = 10): self
    {
        return $this->afterCreating(function (Company $company) use ($count) {
            $draftCount = (int) floor($count * 0.2);     // 20% drafts without schedule
            $scheduledCount = (int) floor($count * 0.2);  // 20% drafts with schedule
            $activeCount = (int) floor($count * 0.4);     // 40% active and generating
            $endedCount = (int) floor($count * 0.1);      // 10% manually ended
            $completedCount = $count - ($draftCount + $scheduledCount + $activeCount + $endedCount); // 10% completed by end conditions

            // Draft recurring invoices (no schedule)
            RecurringInvoice::factory()
                ->count($draftCount)
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            // Draft recurring invoices with schedule
            RecurringInvoice::factory()
                ->count($scheduledCount)
                ->withSchedule()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            // Active recurring invoices with various schedules and historical invoices
            RecurringInvoice::factory()
                ->count($activeCount)
                ->active()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            // Manually ended recurring invoices
            RecurringInvoice::factory()
                ->count($endedCount)
                ->ended()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            // Completed recurring invoices (reached end conditions)
            RecurringInvoice::factory()
                ->count($completedCount)
                ->active()
                ->endAfter($this->faker->numberBetween(5, 12))
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);
        });
    }

    public function withEstimates(int $count = 10): self
    {
        return $this->afterCreating(function (Company $company) use ($count) {
            $draftCount = (int) floor($count * 0.2);     // 20% drafts
            $approvedCount = (int) floor($count * 0.3);   // 30% approved
            $acceptedCount = (int) floor($count * 0.2);  // 20% accepted
            $declinedCount = (int) floor($count * 0.1);  // 10% declined
            $convertedCount = (int) floor($count * 0.1); // 10% converted to invoices
            $expiredCount = $count - ($draftCount + $approvedCount + $acceptedCount + $declinedCount + $convertedCount); // remaining 10%

            Estimate::factory()
                ->count($draftCount)
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Estimate::factory()
                ->count($approvedCount)
                ->approved()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Estimate::factory()
                ->count($acceptedCount)
                ->accepted()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Estimate::factory()
                ->count($declinedCount)
                ->declined()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Estimate::factory()
                ->count($convertedCount)
                ->converted()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Estimate::factory()
                ->count($expiredCount)
                ->expired()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);
        });
    }

    public function withBills(int $count = 10): self
    {
        return $this->afterCreating(function (Company $company) use ($count) {
            $unpaidCount = (int) floor($count * 0.4);
            $paidCount = (int) floor($count * 0.3);
            $partialCount = (int) floor($count * 0.2);
            $overdueCount = $count - ($unpaidCount + $paidCount + $partialCount);

            Bill::factory()
                ->count($unpaidCount)
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Bill::factory()
                ->count($paidCount)
                ->paid()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Bill::factory()
                ->count($partialCount)
                ->partial()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);

            Bill::factory()
                ->count($overdueCount)
                ->overdue()
                ->create([
                    'company_id' => $company->id,
                    'created_by' => $company->user_id,
                    'updated_by' => $company->user_id,
                ]);
        });
    }
}
