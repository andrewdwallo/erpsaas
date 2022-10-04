<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'logo'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function liabilities()
    {
        return $this->hasMany(Liability::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function revenues()
    {
        return $this->hasMany(Revenue::class);
    }

    public function equities()
    {
        return $this->hasMany(Equity::class);
    }

    public function banks()
    {
        return $this->hasMany(Bank::class);
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
