<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $startDate = today()->startOfYear();
        $endDate = today();

        // Change Company Name to ERPSAAS after Login
        $firstCompanyOwner = User::factory()
            ->withPersonalCompany()
            ->create([
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('password'),
                'current_company_id' => 1,
                'created_at' => $startDate->copy(),
            ]);

        $firstCompanyOwner->ownedCompanies->first()->update(['created_at' => $startDate->copy()]);

        // Function to create employees for a company (also creates companies for the employees)
        $createUsers = static function ($company_id, $userCount, $minPercentage, $maxPercentage) use ($endDate, $startDate) {
            $users = User::factory($userCount)
                ->withPersonalCompany()
                ->create([
                    'password' => bcrypt('password'),
                    'current_company_id' => $company_id,
                ]);

            $dateRange = $endDate->diffInMinutes($startDate);
            $minOffset = (int) ($dateRange * $minPercentage);
            $maxOffset = (int) ($dateRange * $maxPercentage);

            for ($i = 0; $i < $userCount; $i++) {
                $increment = (int) ($minOffset + ($i * ($maxOffset - $minOffset) / $userCount));
                $userCreatedAt = $startDate->copy()->addMinutes($increment);

                $user = $users[$i];

                // Randomly assign a role to the user
                $roles = ['editor', 'admin'];
                $role = $roles[array_rand($roles)];

                $user->companies()->attach($company_id, compact('role'));

                $user->update(['created_at' => $userCreatedAt]);
                $user->ownedCompanies->first()?->update(['created_at' => $userCreatedAt]);

                // Generate random created_at date for the company_user pivot table (for employees)
                $user->companies->first()?->users()->updateExistingPivot($user->id, ['created_at' => $userCreatedAt]);
            }
        };

        // Users for the first company (excluding the first company owner)
        $createUsers(1, 5, 0, 0.1);

        // Second company owner
        $secondCompanyOwner = User::factory()
            ->withPersonalCompany()
            ->create([
                'password' => bcrypt('admin2'),
                'current_company_id' => 2,
                'created_at' => $startDate->addMinutes($endDate->diffInMinutes($startDate) * 0.1),
            ]);

        $secondCompanyOwner->ownedCompanies->first()->update(['created_at' => $startDate->addMinutes($endDate->diffInMinutes($startDate) * 0.1)]);

        // Users for the second company (excluding the second company owner)
        $createUsers(2, 5, 0.1, 0.2);

        // Third company owner
        $thirdCompanyOwner = User::factory()
            ->withPersonalCompany()
            ->create([
                'password' => bcrypt('admin3'),
                'current_company_id' => 3,
                'created_at' => $startDate->addMinutes($endDate->diffInMinutes($startDate) * 0.2),
            ]);

        $thirdCompanyOwner->ownedCompanies->first()->update(['created_at' => $startDate->addMinutes($endDate->diffInMinutes($startDate) * 0.2)]);

        // Users for the third company (excluding the third company owner)
        $createUsers(3, 5, 0.2, 0.3);

        // Create employees for each company (each employee has a company)
        $createUsers(4, 5, 0.3, 0.4);
        $createUsers(5, 5, 0.4, 0.5);
        $createUsers(6, 5, 0.5, 0.6);
        $createUsers(7, 5, 0.6, 0.7);
        $createUsers(8, 5, 0.7, 0.8);
        $createUsers(9, 5, 0.8, 0.9);
        $createUsers(10, 5, 0.9, 1);
    }
}
