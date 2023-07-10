<?php

namespace Database\Seeders;

use App\Models\Setting\Discount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = DB::table('companies')->first()->id;
        $userId = DB::table('users')->first()->id;

        $salesDiscounts = [
            '4th of July Sale',
            'End of Year Sale',
            'Black Friday Sale',
            'Cyber Monday Sale',
            'Christmas Sale',
        ];

        $purchaseDiscounts = [
            'Bulk Purchase Bargain',
            'Early Payment Discount',
            'First Time Buyer Special',
            'Recurring Purchase Reward',
            'Referral Program Discount',
        ];

        $shuffledDiscounts = [
            ...array_map(static fn($name) => ['name' => $name, 'type' => 'sales'], $salesDiscounts),
            ...array_map(static fn($name) => ['name' => $name, 'type' => 'purchase'], $purchaseDiscounts)
        ];

        shuffle($shuffledDiscounts);

        $allDiscounts = $shuffledDiscounts;

        foreach ($allDiscounts as $discount) {
            Discount::factory()->create([
                'company_id' => $companyId,
                'name' => $discount['name'],
                'type' => $discount['type'],
                'enabled' => false,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        Discount::where('type', 'sales')
            ->where('company_id', $companyId)
            ->first()
            ->update(['enabled' => true]);

        Discount::where('type', 'purchase')
            ->where('company_id', $companyId)
            ->first()
            ->update(['enabled' => true]);
    }
}
