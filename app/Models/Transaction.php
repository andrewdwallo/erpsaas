<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'type', 'transaction_date', 'description', 'amount', 'running_balance'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
