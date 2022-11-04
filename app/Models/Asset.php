<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $table = 'assets';

    protected $fillable = [
        'company_id',
        'department_id',
        'code',
        'name',
        'type',
        'description',
    ];

    protected $casts = [
        'type' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function income_transactions()
    {
        return $this->hasMany(IncomeTransaction::class);
    }

    public function expense_transactions()
    {
        return $this->hasMany(ExpenseTransaction::class);
    }
}
