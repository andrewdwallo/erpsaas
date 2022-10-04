<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Revenue;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->integer('code');
            $table->string('name');
            $table->string('type');
            $table->string('description');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Revenue::create([
            'code' => '401',
            'name' => 'Fees Earned from Product',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '402',
            'name' => 'Services',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '403',
            'name' => 'Interest',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '404',
            'name' => 'Purchase Discount',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '405',
            'name' => 'Dividends',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '406',
            'name' => 'Earnings from Investments',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '407',
            'name' => 'Sinking Fund Earnings',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '408',
            'name' => 'Sales',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '409',
            'name' => 'Sales Returns & Allowances',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '410',
            'name' => 'Sales Discount',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '411',
            'name' => 'Other Revenue',
            'type' => 'Revenue',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('revenues');
    }
};
