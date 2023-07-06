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
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('currency_code')->default('USD');
            $table->foreignId('sales_tax_id')->constrained('taxes')->restrictOnDelete();
            $table->foreignId('purchase_tax_id')->constrained('taxes')->restrictOnDelete();
            $table->foreignId('income_category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('expense_category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('currency_code')->references('code')->on('currencies')->restrictOnDelete();
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
