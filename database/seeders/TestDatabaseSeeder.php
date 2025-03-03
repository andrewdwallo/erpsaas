<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()
            ->withPersonalCompany()
            ->create([
                'name' => 'Test Company Owner',
                'email' => 'test@gmail.com',
                'password' => bcrypt('password'),
                'current_company_id' => 1,
            ]);
    }
}
