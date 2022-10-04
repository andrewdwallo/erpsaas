<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'department_id',
        'bank_id',
        'account_id',
        'card_id',
        'asset_id',
        'liability_id',
        'expense_id',
        'revenue_id',
        'equity_id',
        'date',
        'number',
        'type',
        'category',
        'merchant_name',
        'description',
        'amount',
        'running_balance',
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function card()
    {
        return $this->belongsTo(Card::class);
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

    public function equity()
    {
        return $this->belongsTo(Equity::class);
    }

    public function revenue()
    {
        return $this->belongsTo(Revenue::class);
    }
}
