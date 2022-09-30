<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'bank_id',
        'account_type',
        'account_name',
        'account_number',
        'currency',
    ];

    protected $casts = [
        'account_type' => 'array',
        'currency' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
