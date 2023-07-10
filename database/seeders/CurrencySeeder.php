<?php

namespace Database\Seeders;

use App\Models\Setting\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = DB::table('companies')->first()->id;
        $userId = DB::table('users')->first()->id;

        Currency::factory()->create([
            'company_id' => $companyId,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}
