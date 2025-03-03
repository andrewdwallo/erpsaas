<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Common\AddressType;
use App\Models\Locale\Country;
use App\Models\Locale\State;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'addresses';

    protected $fillable = [
        'company_id',
        'parent_address_id',
        'type',
        'recipient',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state_id',
        'postal_code',
        'country_code',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => AddressType::class,
    ];

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parentAddress(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_address_id', 'id');
    }

    public function childAddresses(): HasMany
    {
        return $this->hasMany(self::class, 'parent_address_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    protected function addressString(): Attribute
    {
        return Attribute::get(function () {
            $street = array_filter([
                $this->address_line_1,
                $this->address_line_2,
            ]);

            return array_filter([
                implode(', ', $street), // Street 1 & 2 on same line if both exist
                implode(', ', array_filter([
                    $this->city,
                    $this->state->name,
                    $this->postal_code,
                ])),
            ]);
        });
    }

    public function isIncomplete(): bool
    {
        return empty($this->address_line_1) || empty($this->city);
    }
}
