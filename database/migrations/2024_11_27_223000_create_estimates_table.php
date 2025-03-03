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
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('logo')->nullable();
            $table->string('header')->nullable();
            $table->string('subheader')->nullable();
            $table->string('estimate_number')->nullable();
            $table->string('reference_number')->nullable(); // PO, SO, etc.
            $table->date('date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            $table->string('status')->default('draft');
            $table->string('currency_code')->nullable();
            $table->string('discount_method')->default('per_line_item');
            $table->string('discount_computation')->default('percentage');
            $table->integer('discount_rate')->default(0);
            $table->integer('subtotal')->default(0);
            $table->integer('tax_total')->default(0);
            $table->integer('discount_total')->default(0);
            $table->integer('total')->default(0);
            $table->text('terms')->nullable(); // terms, notes
            $table->text('footer')->nullable();
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
        Schema::dropIfExists('estimates');
    }
};
