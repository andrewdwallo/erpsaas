<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a single admin user and their personal company
        User::factory()
            ->withPersonalCompany(function (CompanyFactory $factory) {
                return $factory
                    ->state([
                        'name' => 'JVCSS',
                    ])
                    ->withTransactions()
                    ->withOfferings()
                    ->withClients()
                    ->withVendors()
                    ->withInvoices(50)
                    ->withRecurringInvoices()
                    ->withEstimates(50)
                    ->withBills(50);
            })
            ->create([
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('password'),
                'current_company_id' => 1,  // Assuming this will be the ID of the created company
            ]);
    }
}
