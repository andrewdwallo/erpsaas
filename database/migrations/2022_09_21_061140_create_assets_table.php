<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Bank;
use App\Models\Asset;

return new class extends Migration
{
    /**
     * php artisan migrate:fresh --path=/database/migrations/2022_09_21_061140_create_assets_table.php
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->integer('code');
            $table->string('name');
            $table->json('type');
            $table->string('description')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Asset::create([
            'code' => '101',
            'name' => 'Cash',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '102',
            'name' => 'Bank & Cash',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '103',
            'name' => 'Cash Equivalents',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '104',
            'name' => 'Inventory',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '105',
            'name' => 'Accounts Receivable',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '106',
            'name' => 'Marketable Securities',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '107',
            'name' => 'Prepaid Expenses',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '108',
            'name' => 'Prepaid Liabilities',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '109',
            'name' => 'Other',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '109',
            'name' => 'Buildings',
            'type' => 'Fixed Asset',
        ]);

        Asset::create([
            'code' => '110',
            'name' => 'Computer Equipment',
            'type' => 'Fixed Asset',
        ]);

        Asset::create([
            'code' => '111',
            'name' => 'Software',
            'type' => 'Fixed Asset',
        ]);

        Asset::create([
            'code' => '112',
            'name' => 'Furniture',
            'type' => 'Fixed Asset',
        ]);

        Asset::create([
            'code' => '113',
            'name' => 'Land',
            'type' => 'Fixed Asset',
        ]);

        Asset::create([
            'code' => '114',
            'name' => 'Machinery',
            'type' => 'Fixed Asset',
        ]);

        Asset::create([
            'code' => '115',
            'name' => 'Vehicles',
            'type' => 'Fixed Asset',
        ]);

        Asset::create([
            'code' => '116',
            'name' => 'Patents',
            'type' => 'Fixed Asset',
        ]);

        Asset::create([
            'code' => '117',
            'name' => 'Trademarks',
            'type' => 'Fixed Asset',
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
};
