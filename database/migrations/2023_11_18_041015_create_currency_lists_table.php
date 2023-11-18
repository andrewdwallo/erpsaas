<?php

use App\Facades\Forex;
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
        if (Forex::isDisabled()) {
            return;
        }

        Schema::create('currency_lists', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->string('entity')->nullable();
            $table->boolean('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_lists');
    }
};
