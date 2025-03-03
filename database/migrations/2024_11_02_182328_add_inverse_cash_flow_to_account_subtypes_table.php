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
        Schema::table('account_subtypes', function (Blueprint $table) {
            $table->boolean('inverse_cash_flow')->default(false)->after('multi_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_subtypes', function (Blueprint $table) {
            $table->dropColumn('inverse_cash_flow');
        });
    }
};
