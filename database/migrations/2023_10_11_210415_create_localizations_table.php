<?php

use App\Enums\DateFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Enums\WeekStart;
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
            $table->string('date_format')->default(DateFormat::DEFAULT);
            $table->string('time_format')->default(TimeFormat::DEFAULT);
            $table->unsignedTinyInteger('fiscal_year_end_month')->default(12);
            $table->unsignedTinyInteger('fiscal_year_end_day')->default(31);
            $table->unsignedTinyInteger('week_start')->default(WeekStart::DEFAULT);
            $table->string('number_format')->default(NumberFormat::DEFAULT);
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
