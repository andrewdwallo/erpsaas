<?php

use App\Enums\Font;
use App\Enums\PaymentTerms;
use App\Enums\Template;
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
            $table->unsignedTinyInteger('number_digits')->default(5);
            $table->unsignedBigInteger('number_next')->default(1);
            $table->string('payment_terms')->default(PaymentTerms::DEFAULT);
            $table->string('header')->nullable();
            $table->string('subheader')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->string('accent_color')->default('#4F46E5');
            $table->string('font')->default(Font::DEFAULT);
            $table->string('template')->default(Template::DEFAULT);
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
