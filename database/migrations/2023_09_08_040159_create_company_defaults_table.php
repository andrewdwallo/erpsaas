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
        Schema::create('company_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->restrictOnDelete();
            $table->string('currency_code')->nullable();
            $table->foreignId('sales_tax_id')->nullable()->constrained('taxes')->cascadeOnDelete();
            $table->foreignId('purchase_tax_id')->nullable()->constrained('taxes')->cascadeOnDelete();
            $table->foreignId('sales_discount_id')->nullable()->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('purchase_discount_id')->nullable()->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->foreign(['company_id', 'currency_code'])
                ->references(['company_id', 'code'])
                ->on('currencies')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_defaults');
    }
};
