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
        Schema::table('company_user', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->after('user_id');
            $table->string('employment_type')->nullable()->after('role');
            $table->date('hire_date')->nullable()->after('employment_type');
            $table->date('start_date')->nullable()->after('hire_date');
            $table->unsignedBigInteger('department_id')->nullable()->after('start_date');
            $table->string('job_title')->nullable()->after('department_id');
            $table->string('photo')->nullable()->after('job_title');
            $table->date('date_of_birth')->nullable()->after('photo');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('marital_status')->nullable()->after('gender');
            $table->string('nationality')->nullable()->after('marital_status');
            $table->bigInteger('compensation_amount')->nullable()->after('nationality');
            $table->string('compensation_type')->nullable()->after('compensation_amount');
            $table->string('compensation_frequency')->nullable()->after('compensation_type');
            $table->string('bank_account_number')->nullable()->after('compensation_frequency');
            $table->string('education_level')->nullable()->after('bank_account_number');
            $table->string('field_of_study')->nullable()->after('education_level');
            $table->string('school_name')->nullable()->after('field_of_study');
            $table->string('emergency_contact_name')->nullable()->after('school_name');
            $table->string('emergency_contact_phone_number')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_email')->nullable()->after('emergency_contact_phone_number');

            $table->foreign('contact_id')->references('id')->on('contacts')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_user', function (Blueprint $table) {
            $table->dropColumn([
                'contact_id',
                'employment_type',
                'hire_date',
                'start_date',
                'department_id',
                'job_title',
                'photo',
                'date_of_birth',
                'gender',
                'marital_status',
                'nationality',
                'compensation_amount',
                'compensation_type',
                'compensation_frequency',
                'bank_account_number',
                'education_level',
                'field_of_study',
                'school_name',
                'emergency_contact_name',
                'emergency_contact_phone_number',
                'emergency_contact_email',
            ]);
        });
    }
};
