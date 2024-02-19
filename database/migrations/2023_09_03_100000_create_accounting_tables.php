<?php

use App\Enums\BankAccountType;
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
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('external_institution_id')->nullable(); // Plaid
            $table->string('name')->index();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('account_subtypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->boolean('multi_currency')->default(false);
            $table->string('category');
            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subtype_id')->nullable()->constrained('account_subtypes')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('type')->nullable();
            $table->string('code')->nullable()->index();
            $table->string('name')->nullable()->index();
            $table->string('currency_code')->nullable();
            $table->bigInteger('starting_balance')->default(0);
            $table->bigInteger('debit_balance')->default(0);
            $table->bigInteger('credit_balance')->default(0);
            $table->bigInteger('net_movement')->default(0);
            $table->bigInteger('ending_balance')->default(0);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('default')->default(false);
            $table->unsignedBigInteger('accountable_id')->nullable();
            $table->string('accountable_type')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->string('type')->default(BankAccountType::DEFAULT);
            $table->string('number', 20);
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('connected_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->string('external_account_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('identifier')->unique()->nullable(); // Plaid
            $table->string('item_id')->nullable();
            $table->string('name');
            $table->string('mask');
            $table->string('type')->default(BankAccountType::DEFAULT);
            $table->string('subtype')->nullable();
            $table->boolean('import_transactions')->default(false);
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
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('account_subtypes');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('connected_bank_accounts');
    }
};
