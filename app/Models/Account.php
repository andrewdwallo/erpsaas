<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'account_number', 'currency', 'starting_balance', 'bank_name', 'bank_phone', 'bank_address'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}
