<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->boolean('qr_bill_enabled')->default(false);
            $table->enum('qr_account_type', ['qr-iban', 'iban'])->default('qr-iban');
            $table->string('qr_iban', 34)->nullable();
            $table->string('iban', 34)->nullable();
            $table->enum('default_reference_type', ['QRR', 'SCOR', 'NON'])->nullable();
            $table->string('unstructured_message', 140)->nullable();
            $table->text('bill_information')->nullable();
            $table->enum('layout_mode', ['append_page', 'footer_on_last_page'])->default('append_page');
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'qr_bill_enabled',
                'qr_account_type',
                'qr_iban',
                'iban',
                'default_reference_type',
                'unstructured_message',
                'bill_information',
                'layout_mode',
            ]);
        });
    }
};
