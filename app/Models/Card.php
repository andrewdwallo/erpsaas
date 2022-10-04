<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'bank_id',
        'account_id',
        'card_type',
        'card_name',
        'card_number',
        'name_on_card',
        'expiration_date',
        'security_code',
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

    public function account()
    {
        return $this->belongsTo(Account::class);
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
