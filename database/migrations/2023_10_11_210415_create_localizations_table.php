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
        Schema::create('localizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('language')->default('en');
            $table->string('timezone')->nullable();
            $table->string('date_format')->default('M j, Y');
            $table->string('time_format')->default('g:i A');
            $table->unsignedTinyInteger('fiscal_year_end_month')->default(12);
            $table->unsignedTinyInteger('fiscal_year_end_day')->default(31);
            $table->unsignedTinyInteger('week_start')->default(1);
            $table->string('number_format')->default('comma_dot');
            $table->boolean('percent_first')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localizations');
    }
};
