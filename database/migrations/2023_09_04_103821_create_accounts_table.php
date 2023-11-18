<?php

use App\Enums\AccountStatus;
use App\Enums\AccountType;
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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default(AccountType::DEFAULT);
            $table->string('name', 100)->index();
            $table->string('number', 20);
            $table->string('currency_code');
            $table->bigInteger('opening_balance')->default(0);
            $table->bigInteger('balance')->default(0);
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default(AccountStatus::DEFAULT);
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_phone', 20)->nullable();
            $table->text('bank_address')->nullable();
            $table->string('bank_website', 255)->nullable();
            $table->string('bic_swift_code', 11)->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('aba_routing_number', 9)->nullable();
            $table->string('ach_routing_number', 9)->nullable();
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'number']);

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
        Schema::dropIfExists('accounts');
    }
};
