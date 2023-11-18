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
        Schema::create('account_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('account_id');
            $table->string('type');
            $table->string('name');
            $table->string('number');
            $table->string('currency_code');
            $table->bigInteger('opening_balance');
            $table->bigInteger('balance');
            $table->bigInteger('exchange_rate');
            $table->string('status');
            $table->json('actions')->nullable();
            $table->string('description')->nullable();
            $table->boolean('enabled');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_histories');
    }
};
