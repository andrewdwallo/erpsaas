<?php

namespace Database\Seeders;

use App\Models\Setting\Tax;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = DB::table('companies')->first()->id;
        $userId = DB::table('users')->first()->id;

        $salesTaxes = [
            'Goods and Services Tax (GST)',
            'Value Added Tax (VAT)',
            'State Sales Tax',
            'Local Sales Tax',
            'Excise Tax',
        ];

        $purchaseTaxes = [
            'Import Duty',
            'Customs Duty',
            'Value Added Tax (VAT)',
            'Luxury Tax',
            'Environmental Tax',
        ];

        $shuffledTaxes = [
            ...array_map(static fn($name) => ['name' => $name, 'type' => 'sales'], $salesTaxes),
            ...array_map(static fn($name) => ['name' => $name, 'type' => 'purchase'], $purchaseTaxes)
        ];

        shuffle($shuffledTaxes);

        $allTaxes = $shuffledTaxes;

        foreach ($allTaxes as $tax) {
            Tax::factory()->create([
                'company_id' => $companyId,
                'name' => $tax['name'],
                'type' => $tax['type'],
                'enabled' => false,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        Tax::where('type', 'sales')
            ->where('company_id', $companyId)
            ->first()
            ->update(['enabled' => true]);

        Tax::where('type', 'purchase')
            ->where('company_id', $companyId)
            ->first()
            ->update(['enabled' => true]);
    }
}
