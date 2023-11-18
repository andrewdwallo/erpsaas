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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('address', 255)->nullable();
            $table->unsignedMediumInteger('city_id')->nullable()->index();
            $table->string('zip_code', 20)->nullable();
            $table->unsignedSmallInteger('state_id')->nullable()->index();
            $table->string('country')->nullable();
            $table->string('timezone')->nullable();
            $table->string('language')->nullable();
            $table->string('contact_method')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('currency_code', 10);
            $table->string('website', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->unique(['company_id', 'type', 'email']);

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
        Schema::dropIfExists('contacts');
    }
};
