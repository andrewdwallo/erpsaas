<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeTransaction extends Model
{
    use HasFactory;

    protected $table = 'income_transactions';

    protected $fillable = [
        'company_id',
        'department_id',
        'bank_id',
        'account_id',
        'card_id',
        'revenue_id',
        'asset_id',
        'paid_at',
        'number',
        'merchant_name',
        'description',
        'amount',
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

    public function revenue()
    {
        return $this->belongsTo(Revenue::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
