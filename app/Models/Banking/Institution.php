<?php

namespace App\Models\Banking;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Institution extends Model
{
    use HasFactory;

    protected $table = 'institutions';

    protected $fillable = [
        'external_institution_id',
        'name',
        'logo',
        'website',
        'phone',
        'address',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'logo_url',
    ];

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'institution_id');
    }

    public function connectedBankAccounts(): HasMany
    {
        return $this->hasMany(ConnectedBankAccount::class, 'institution_id');
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(static function (mixed $value, array $attributes): ?string {
            if ($attributes['logo']) {
                return Storage::disk('public')->url($attributes['logo']);
            }

            return null;
        });
    }

    public function logo(): Attribute
    {
        return Attribute::set(static function (mixed $value): ?string {
            if ($value) {
                $decoded = base64_decode($value);
                $filename = 'institution_logo_' . uniqid('', true) . '.png';
                Storage::disk('public')->put($filename, $decoded);

                return $filename;
            }

            return null;
        });
    }
}
