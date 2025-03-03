<?php

namespace App\Models\Core;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Models\User;
use Database\Factories\Core\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Wallo\FilamentCompanies\FilamentCompanies;

class Department extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')
            ->whereKeyNot($this->getKey());
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function employeeships(): HasMany
    {
        return $this->hasMany(FilamentCompanies::employeeshipModel(), 'department_id');
    }

    protected static function newFactory(): Factory
    {
        return DepartmentFactory::new();
    }
}
