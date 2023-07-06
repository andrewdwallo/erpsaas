<?php

namespace Database\Seeders;

use App\Models\Setting\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = DB::table('companies')->first()->id;
        $userId = DB::table('users')->first()->id;

        $incomeCategories = [
            'Salary',
            'Bonus',
            'Interest',
            'Dividends',
            'Rentals',
        ];

        $expenseCategories = [
            'Rent',
            'Utilities',
            'Food',
            'Transportation',
            'Entertainment',
        ];

        // Merge and shuffle the sales and purchase taxes
        $shuffledCategories = [
            ...array_map(static fn($name) => ['name' => $name, 'type' => 'income'], $incomeCategories),
            ...array_map(static fn($name) => ['name' => $name, 'type' => 'expense'], $expenseCategories)
        ];

        shuffle($shuffledCategories);

        $allCategories = $shuffledCategories;

        // Create each category
        foreach ($allCategories as $category) {
            Category::factory()->create([
                'company_id' => $companyId,
                'name' => $category['name'],
                'type' => $category['type'],
                'enabled' => false,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        // Set the first income category as enabled
        Category::where('type', 'income')
            ->where('company_id', $companyId)
            ->first()
            ->update(['enabled' => true]);

        // Set the first expense category as enabled
        Category::where('type', 'expense')
            ->where('company_id', $companyId)
            ->first()
            ->update(['enabled' => true]);
    }
}
