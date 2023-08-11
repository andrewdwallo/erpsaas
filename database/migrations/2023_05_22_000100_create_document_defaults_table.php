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
        Schema::create('document_defaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('document_logo')->nullable();
            $table->string('number_prefix')->nullable();
            $table->unsignedTinyInteger('number_digits')->default(5); // Adjusted this
            $table->unsignedBigInteger('number_next')->default(1);   // Adjusted this to allow larger invoice numbers
            $table->string('payment_terms')->nullable();
            $table->string('title')->nullable();
            $table->string('subheading')->nullable();
            $table->text('terms')->nullable();
            $table->string('footer')->nullable();
            $table->string('accent_color')->nullable();
            $table->string('template')->default('default');
            $table->string('item_column')->nullable();
            $table->string('unit_column')->nullable();
            $table->string('price_column')->nullable();
            $table->string('amount_column')->nullable();
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
        Schema::dropIfExists('document_defaults');
    }
};
