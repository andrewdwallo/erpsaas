<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Liability;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            $table->integer('code');
            $table->string('name');
            $table->string('type');
            $table->string('description');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Liability::create([
            'code' => '201',
            'name' => 'Accounts Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '202',
            'name' => 'Sales Taxes Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '203',
            'name' => 'Payroll Taxes Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '204',
            'name' => 'Income Taxes Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '205',
            'name' => 'Interest Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '206',
            'name' => 'Bank Account Overdrafts',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '207',
            'name' => 'Accrued Expenses',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '208',
            'name' => 'Customer Deposits',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '209',
            'name' => 'Dividends Declared',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '210',
            'name' => 'Short-term Loans',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '211',
            'name' => 'Current Maturities of Long-term Debt',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '212',
            'name' => 'Wages Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '213',
            'name' => 'Wages Payable - Payroll',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '214',
            'name' => 'Employee Benefits Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '215',
            'name' => 'Employee Deductions Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '216',
            'name' => 'Clearing Account',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '220',
            'name' => 'Long-term Loans',
            'type' => 'Noncurrent Liabilities',
        ]);

        Liability::create([
            'code' => '221',
            'name' => 'Long-term Lease Obligations',
            'type' => 'Noncurrent Liabilities',
        ]);

        Liability::create([
            'code' => '222',
            'name' => 'Bonds Payable',
            'type' => 'Noncurrent Liabilities',
        ]);

        Liability::create([
            'code' => '223',
            'name' => 'Deferred Revenue',
            'type' => 'Noncurrent Liabilities',
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('liabilities');
    }
};
