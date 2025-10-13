<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            // Remove the separate qr_iban and iban fields
            $table->dropColumn(['qr_iban', 'iban', 'qr_account_type', 'default_reference_type']);
            
            // Add simplified fields
            $table->string('qr_bill_iban', 34)->nullable(); // Single IBAN field (QR-IBAN or normal IBAN)
            $table->string('qr_bill_besr_id', 10)->nullable(); // BESR-ID from bank (for QR reference generation)
            $table->string('qr_bill_reference_prefix', 20)->nullable(); // Optional prefix for reference numbers
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            // Restore original structure
            $table->dropColumn(['qr_bill_iban', 'qr_bill_besr_id', 'qr_bill_reference_prefix']);
            
            $table->enum('qr_account_type', ['qr-iban', 'iban'])->default('qr-iban');
            $table->string('qr_iban', 34)->nullable();
            $table->string('iban', 34)->nullable();
            $table->enum('default_reference_type', ['QRR', 'SCOR', 'NON'])->nullable();
        });
    }
};