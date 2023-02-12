<?php

use App\Models\Asset;
use App\Models\Equity;
use App\Models\Expense;
use App\Models\Liability;
use App\Models\Revenue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->json('type');
            $table->string('description')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['code', 'name']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->json('type');
            $table->string('description')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['code', 'name']);
        });

        Schema::create('liabilities', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->json('type');
            $table->string('description')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['code', 'name']);
        });

        Schema::create('equities', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->json('type');
            $table->string('description')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['code', 'name']);
        });

        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->json('type');
            $table->string('description')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['code', 'name']);
        });

        //Default Assets
        Asset::create([
            'code' => '100',
            'name' => 'Cash',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '101',
            'name' => 'Bank & Cash',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '102',
            'name' => 'Cash Equivalents',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '103',
            'name' => 'Inventory',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '104',
            'name' => 'Accounts Receivable',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '105',
            'name' => 'Marketable Securities',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '106',
            'name' => 'Prepaid Expenses',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '107',
            'name' => 'Prepaid Liabilities',
            'type' => 'Current Asset',
        ]);

        Asset::create([
            'code' => '108',
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

        //Default Expenses
        Expense::create([
            'code' => '500',
            'name' => 'Cost of Goods Sold',
            'type' => 'Direct Costs',
        ]);

        Expense::create([
            'code' => '501',
            'name' => 'Amortization Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '502',
            'name' => 'Depletion Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '503',
            'name' => 'Depreciation Expense - Automobiles',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '504',
            'name' => 'Depreciation Expense - Building',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '505',
            'name' => 'Depreciation Expense - Furniture',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '506',
            'name' => 'Depreciation Expense - Land Improvements',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '507',
            'name' => 'Depreciation Expense - Library',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '508',
            'name' => 'Depreciation Expense - Machinery',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '509',
            'name' => 'Depreciation Expense - Office Equipment',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '510',
            'name' => 'Office Salaries Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '511',
            'name' => 'Sales Salaries Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '512',
            'name' => 'Salaries Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '513',
            'name' => 'Employee Benefits Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '514',
            'name' => 'Payroll Taxes Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '515',
            'name' => 'Rent',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '516',
            'name' => 'Advertising',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '517',
            'name' => 'Bank Service Charges',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '518',
            'name' => 'Janitorial Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '519',
            'name' => 'Consulting & Accounting',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '520',
            'name' => 'Entertainment',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '521',
            'name' => 'Postage & Delivery',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '522',
            'name' => 'General Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '523',
            'name' => 'Insurance',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '524',
            'name' => 'Legal Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '525',
            'name' => 'Utilities',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '526',
            'name' => 'Automobile Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '527',
            'name' => 'Office Expenses',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '528',
            'name' => 'Repairs & Maintenance',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '529',
            'name' => 'Wages & Salaries',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '530',
            'name' => 'Dues & Subscriptions',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '531',
            'name' => 'Telephone & Internet',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '532',
            'name' => 'Travel',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '533',
            'name' => 'Bad Debts',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '534',
            'name' => 'Interest Expense',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '535',
            'name' => 'Bank Revaluations',
            'type' => 'Expense',
        ]);

        Expense::create([
            'code' => '536',
            'name' => 'Sales Discount',
            'type' => 'Expense',
        ]);

        //Default Liabilities
        Liability::create([
            'code' => '200',
            'name' => 'Accounts Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '201',
            'name' => 'Sales Taxes Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '202',
            'name' => 'Payroll Taxes Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '203',
            'name' => 'Income Taxes Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '204',
            'name' => 'Interest Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '205',
            'name' => 'Bank Account Overdrafts',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '206',
            'name' => 'Accrued Expenses',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '207',
            'name' => 'Customer Deposits',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '208',
            'name' => 'Dividends Declared',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '209',
            'name' => 'Short-term Loans',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '210',
            'name' => 'Current Maturities of Long-term Debt',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '211',
            'name' => 'Wages Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '212',
            'name' => 'Wages Payable - Payroll',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '213',
            'name' => 'Employee Benefits Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '214',
            'name' => 'Employee Deductions Payable',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '215',
            'name' => 'Clearing Account',
            'type' => 'Current Liabilities',
        ]);

        Liability::create([
            'code' => '216',
            'name' => 'Long-term Loans',
            'type' => 'Noncurrent Liabilities',
        ]);

        Liability::create([
            'code' => '217',
            'name' => 'Long-term Lease Obligations',
            'type' => 'Noncurrent Liabilities',
        ]);

        Liability::create([
            'code' => '218',
            'name' => 'Bonds Payable',
            'type' => 'Noncurrent Liabilities',
        ]);

        Liability::create([
            'code' => '219',
            'name' => 'Deferred Revenue',
            'type' => 'Noncurrent Liabilities',
        ]);

        //Default Equities
        Equity::create([
            'code' => '300',
            'name' => 'Owners Capital',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '301',
            'name' => 'Owners Withdrawals',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '302',
            'name' => 'Owners Draw',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '303',
            'name' => 'Retained Earnings',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '304',
            'name' => 'Common Stock, par value',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '305',
            'name' => 'Common Stock, no par value',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '306',
            'name' => 'Common Stock, stated value',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '307',
            'name' => 'Common Stock Dividend Distributable',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '308',
            'name' => 'Paid-in Capital in Excess of Par Value, Common Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '309',
            'name' => 'Paid-in Capital in Excess of Stated Value, no par Common Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '310',
            'name' => 'Paid-in Capital from Retirement of Common Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '311',
            'name' => 'Paid-in Capital, Treasury Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '312',
            'name' => 'Preferred Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '313',
            'name' => 'Paid-in Capital in Excess of Par Value, Preferred Stock',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '314',
            'name' => 'Cash Dividends',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '315',
            'name' => 'Stock Dividends',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '316',
            'name' => 'Treasury Stock, Common',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '317',
            'name' => 'Unrealized Gain',
            'type' => 'Equity',
        ]);

        Equity::create([
            'code' => '318',
            'name' => 'Unrealized Loss',
            'type' => 'Equity',
        ]);

        //Default Revenues
        Revenue::create([
            'code' => '400',
            'name' => 'Fees Earned from Product',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '401',
            'name' => 'Services',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '402',
            'name' => 'Interest',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '403',
            'name' => 'Purchase Discount',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '404',
            'name' => 'Dividends',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '405',
            'name' => 'Earnings from Investments',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '406',
            'name' => 'Sinking Fund Earnings',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '407',
            'name' => 'Sales',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '408',
            'name' => 'Sales Returns & Allowances',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '409',
            'name' => 'Sales Discount',
            'type' => 'Revenue',
        ]);

        Revenue::create([
            'code' => '410',
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
        Schema::dropIfExists('assets');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('liabilities');
        Schema::dropIfExists('equities');
        Schema::dropIfExists('revenues');
    }
};
