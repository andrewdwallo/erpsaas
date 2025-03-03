<?php

namespace App\Models\Common;

use App\Concerns\Blamable;
use App\Concerns\CompanyOwned;
use App\Enums\Common\ContactType;
use Database\Factories\Common\ContactFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Contact extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'contacts';

    protected $fillable = [
        'company_id',
        'type',
        'first_name',
        'last_name',
        'email',
        'phones',
        'is_primary',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'type' => ContactType::class,
        'phones' => 'array',
    ];

    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(function () {
            return trim("{$this->first_name} {$this->last_name}");
        });
    }

    protected function primaryPhone(): Attribute
    {
        return Attribute::get(function () {
            return $this->getPhoneByType('primary');
        });
    }

    protected function mobilePhone(): Attribute
    {
        return Attribute::get(function () {
            return $this->getPhoneByType('mobile');
        });
    }

    protected function faxPhone(): Attribute
    {
        return Attribute::get(function () {
            return $this->getPhoneByType('fax');
        });
    }

    protected function tollFreePhone(): Attribute
    {
        return Attribute::get(function () {
            return $this->getPhoneByType('toll_free');
        });
    }

    protected function firstAvailablePhone(): Attribute
    {
        return Attribute::get(function () {
            $priority = ['primary', 'mobile', 'toll_free', 'fax'];

            foreach ($priority as $type) {
                $phone = $this->getPhoneByType($type);
                if ($phone) {
                    return $phone;
                }
            }

            return null; // Return null if no phone numbers are available
        });
    }

    private function getPhoneByType(string $type): ?string
    {
        if (! is_array($this->phones)) {
            return null;
        }

        foreach ($this->phones as $phone) {
            if ($phone['type'] === $type) {
                return $phone['data']['number'] ?? null;
            }
        }

        return null;
    }

    protected static function newFactory(): Factory
    {
        return ContactFactory::new();
    }
}
