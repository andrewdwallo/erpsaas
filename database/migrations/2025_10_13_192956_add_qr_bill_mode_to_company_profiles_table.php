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
        Schema::table('company_profiles', function (Blueprint $table) {
            // QR Bill mode: 'iban' for normal IBAN without reference, 'qr_iban' for QR-IBAN with reference
            $table->enum('qr_bill_mode', ['iban', 'qr_iban'])->default('iban')->after('qr_bill_iban');
            
            // Reference pattern for QR-IBAN mode (e.g., 'INV-{invoice_number}', '{invoice_id}', etc.)
            $table->string('qr_bill_reference_pattern')->nullable()->after('qr_bill_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn(['qr_bill_mode', 'qr_bill_reference_pattern']);
        });
    }
};
