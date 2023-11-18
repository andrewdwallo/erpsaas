<?php

namespace App\Models;

use App\Models\Common\Contact;
use App\Models\Core\Department;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\Employeeship as FilamentCompaniesEmployeeship;
use Wallo\FilamentCompanies\FilamentCompanies;

class Employeeship extends FilamentCompaniesEmployeeship
{
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    protected $fillable = [
        'company_id',
        'user_id',
        'contact_id',
        'role',
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
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'user_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    public function getNameAttribute(): string
    {
        return $this->user->name;
    }
}
