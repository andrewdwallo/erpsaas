<?php

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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_type')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable()->unique();
            $table->string('routing_number_paperless_and_electronic')->nullable();
            $table->string('routing_number_wires')->nullable();
            $table->string('account_opened_date')->nullable();
            $table->string('currency')->nullable()->unique();
            $table->string('starting_balance')->nullable();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('accounts');
    }
};
