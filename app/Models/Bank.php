<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'bank_type',
        'bank_name',
        'bank_phone',
        'bank_address',
    ];

    protected $casts = [
        'bank_type' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function liability()
    {
        return $this->belongsTo(Liability::class);
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function revenue()
    {
        return $this->belongsTo(Revenue::class);
    }

    public function equity()
    {
        return $this->belongsTo(Equity::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
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
