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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('logo')->nullable();
            $table->string('header')->nullable();
            $table->string('subheader')->nullable();
            $table->string('order_number')->nullable(); // PO, SO, etc.
            $table->string('payment_terms')->default('due_upon_receipt');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('frequency')->default('monthly'); // daily, weekly, monthly, yearly, custom
            $table->string('interval_type')->nullable(); // for custom frequency "day(s), week(s), month(s), year(s)"
            $table->tinyInteger('interval_value')->nullable(); // "every x" value (only for custom frequency)
            $table->tinyInteger('month')->nullable(); // 1-12 for yearly frequency
            $table->tinyInteger('day_of_month')->nullable(); // 1-31 for monthly, yearly, custom yearly frequency
            $table->tinyInteger('day_of_week')->nullable(); // 1-7 for weekly, custom weekly frequency
            $table->date('start_date')->nullable();
            $table->string('end_type')->default('never'); // never, after, on
            $table->smallInteger('max_occurrences')->nullable(); // when end_type is 'after'
            $table->date('end_date')->nullable(); // when end_type is 'on'
            $table->smallInteger('occurrences_count')->default(0);
            $table->string('timezone')->default(config('app.timezone'));
            $table->date('next_date')->nullable();
            $table->date('last_date')->nullable();
            $table->boolean('auto_send')->default(false);
            $table->time('send_time')->default('09:00:00');
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
        Schema::dropIfExists('recurring_invoices');
    }
};
