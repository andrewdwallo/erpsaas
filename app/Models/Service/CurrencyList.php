<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyList extends Model
{
    use HasFactory;

    protected $table = 'currency_lists';

    protected $fillable = [
        'code',
        'name',
        'entity',
        'available',
    ];

    protected $casts = [
        'available' => 'boolean',
    ];

    public function isAvailable(): bool
    {
        return $this->available === true;
    }
}
