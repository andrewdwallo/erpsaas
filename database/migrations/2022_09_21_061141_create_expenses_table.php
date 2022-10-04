<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Expense;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->integer('code');
            $table->string('name');
            $table->string('type');
            $table->string('description');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Expense::create([
            'code' => '501',
            'name' => 'Cost of Goods Sold',
            'type' => 'Direct Costs',
        ]);

        Expense::create([
            'code' => '502',
            'name' => 'Amortization Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '503',
            'name' => 'Depletion Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '504',
            'name' => 'Depreciation Expense - Automobiles',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '505',
            'name' => 'Depreciation Expense - Building',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '506',
            'name' => 'Depreciation Expense - Furniture',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '507',
            'name' => 'Depreciation Expense - Land Improvements',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '508',
            'name' => 'Depreciation Expense - Library',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '509',
            'name' => 'Depreciation Expense - Machinery',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '510',
            'name' => 'Depreciation Expense - Office Equipment',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '511',
            'name' => 'Office Salaries Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '512',
            'name' => 'Sales Salaries Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '513',
            'name' => 'Salaries Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '514',
            'name' => 'Employee Benefits Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '515',
            'name' => 'Payroll Taxes Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '516',
            'name' => 'Rent',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '517',
            'name' => 'Advertising',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '518',
            'name' => 'Bank Service Charges',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '519',
            'name' => 'Janitorial Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '520',
            'name' => 'Consulting & Accounting',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '521',
            'name' => 'Entertainment',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '522',
            'name' => 'Postage & Delivery',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '523',
            'name' => 'General Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '524',
            'name' => 'Insurance',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '525',
            'name' => 'Legal Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '526',
            'name' => 'Utilities',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '527',
            'name' => 'Automobile Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '528',
            'name' => 'Office Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '529',
            'name' => 'Repairs & Maintenance',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '530',
            'name' => 'Wages & Salaries',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '531',
            'name' => 'Dues & Subscriptions',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '532',
            'name' => 'Telephone & Internet',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '533',
            'name' => 'Travel',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '534',
            'name' => 'Bad Debts',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '535',
            'name' => 'Interest Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '536',
            'name' => 'Bank Revaluations',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '537',
            'name' => 'Sales Discount',
            'type' => 'Expense',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expenses');
    }
};
