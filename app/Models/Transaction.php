<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'bank_id',
        'account_id',
        'card_id',
        'date',
        'merchant_name',
        'description',
        'amount',
        'running_balance',
        'available_balance',
        'debit_amount',
        'credit_amount',
        'iso_currency_code',
        'unofficial_currency_code',
        'category',
        'check_number',
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

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
}
