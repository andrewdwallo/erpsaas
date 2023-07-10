<?php

namespace Database\Seeders;

use App\Models\Banking\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::factory()
            ->count(5)
            ->create();
    }
}
