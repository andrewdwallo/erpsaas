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
