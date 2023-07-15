<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('default_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('currency_code')->nullable();
            $table->foreignId('sales_tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('purchase_tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('sales_discount_id')->nullable()->constrained('discounts')->nullOnDelete();
            $table->foreignId('purchase_discount_id')->nullable()->constrained('discounts')->nullOnDelete();
            $table->foreignId('income_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('currency_code')->references('code')->on('currencies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_settings');
    }
};
