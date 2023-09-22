<?php

namespace App\Models\Core;

use App\Models\Common\Contact;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Core\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class Department extends Model
{
    use Blamable, CompanyOwned, HasFactory;

    protected $table = 'departments';

    protected $fillable = [
        'company_id',
        'manager_id',
        'parent_id',
        'name',
        'description',
        'created_by',
        'updated_by',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'manager_id');
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function employeeships(): HasMany
    {
        return $this->hasMany(FilamentCompanies::employeeshipModel(), 'department_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    protected static function newFactory(): Factory
    {
        return DepartmentFactory::new();
    }
}
