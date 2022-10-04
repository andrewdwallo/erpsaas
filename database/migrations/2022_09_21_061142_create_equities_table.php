<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Equity;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equities', function (Blueprint $table) {
            $table->id();
            $table->integer('code');
            $table->string('name');
            $table->string('type');
            $table->string('description');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Equity::create([
            'code' => '301',
            'name' => 'Owners Capital',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '302',
            'name' => 'Owners Withdrawals',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '303',
            'name' => 'Owners Draw',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '304',
            'name' => 'Retained Earnings',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '305',
            'name' => 'Common Stock, par value',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '306',
            'name' => 'Common Stock, no par value',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '307',
            'name' => 'Common Stock, stated value',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '308',
            'name' => 'Common Stock Dividend Distributable',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '309',
            'name' => 'Paid-in Capital in Excess of Par Value, Common Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '310',
            'name' => 'Paid-in Capital in Excess of Stated Value, no par Common Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '311',
            'name' => 'Paid-in Capital from Retirement of Common Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '312',
            'name' => 'Paid-in Capital, Treasury Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '313',
            'name' => 'Preferred Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '314',
            'name' => 'Paid-in Capital in Excess of Par Value, Preferred Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '315',
            'name' => 'Cash Dividends',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '316',
            'name' => 'Stock Dividends',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '317',
            'name' => 'Treasury Stock, Common',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '318',
            'name' => 'Unrealized Gain',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '319',
            'name' => 'Unrealized Loss',
            'type' => 'Equity',
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('equities');
    }
};
