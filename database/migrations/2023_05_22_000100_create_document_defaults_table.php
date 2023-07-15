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
            $table->string('document_number_prefix')->nullable();
            $table->string('document_number_digits')->default(5);
            $table->string('document_number_next')->default(1);
            $table->string('payment_terms')->nullable();
            $table->string('template')->default('default');
            $table->string('title')->nullable();
            $table->string('subheading')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('footer')->nullable();
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
