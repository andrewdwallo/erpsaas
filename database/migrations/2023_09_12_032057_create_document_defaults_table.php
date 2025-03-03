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
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('logo')->nullable();
            $table->boolean('show_logo')->default(false);
            $table->string('number_prefix')->nullable();
            $table->string('payment_terms')->default('due_upon_receipt');
            $table->string('header')->nullable();
            $table->string('subheader')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->string('accent_color')->nullable();
            $table->string('font')->nullable();
            $table->string('template')->nullable();
            $table->json('item_name')->nullable();
            $table->json('unit_name')->nullable();
            $table->json('price_name')->nullable();
            $table->json('amount_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'type']);
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
