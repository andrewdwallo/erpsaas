<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Refresh a specific migration table 
     * php artisan migrate:refresh --path=/database/migrations/2022_09_21_061139_create_transactions_table.php
     */

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('date')->nullable();
            $table->string('merchant_name')->nullable();
            $table->string('description')->nullable();
            $table->double('amount')->nullable();
            $table->string('running_balance')->nullable();
            $table->string('available_balance')->nullable();
            $table->string('debit_amount')->nullable();
            $table->string('credit_amount')->nullable();
            $table->string('category')->nullable();
            $table->string('check_number')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
