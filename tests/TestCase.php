<?php

namespace Tests;

use App\Models\Common\Offering;
use App\Models\Company;
use App\Models\User;
use App\Testing\TestsReport;
use Database\Seeders\TestDatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Features\SupportTesting\Testable;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Indicates whether the default seeder should run before each test.
     */
    protected bool $seed = true;

    /**
     * Run a specific seeder before each test.
     */
    protected string $seeder = TestDatabaseSeeder::class;

    protected User $testUser;

    protected ?Company $testCompany;

    protected function setUp(): void
    {
        parent::setUp();

        Testable::mixin(new TestsReport);

        $this->testUser = User::first();

        $this->testCompany = $this->testUser->ownedCompanies->first();

        $this->testUser->switchCompany($this->testCompany);

        $this->actingAs($this->testUser);

        Filament::setTenant($this->testCompany);
    }

    public function withOfferings(): static
    {
        Offering::factory()
            ->for($this->testCompany)
            ->sellable()
            ->withSalesAdjustments()
            ->purchasable()
            ->withPurchaseAdjustments()
            ->create();

        return $this;
    }
}
