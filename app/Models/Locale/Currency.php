<?php

namespace App\Models\Locale;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Currency extends Model
{
    protected $table = 'currencies';

    protected $guarded = [];

    protected $casts = [
        'code' => 'string',
        'name' => 'string',
        'symbol' => 'string',
        'precision' => 'int',
        'decimal_mark' => 'string',
        'thousands_separator' => 'string',
        'symbol_first' => 'bool',
        'subunit' => 'int',
    ];

    public function countries(): HasMany
    {
        return $this->hasMany(Country::class, 'currency_code', 'code');
    }

    public static function allCached(): Collection
    {
        return collect(Cache::get('currencies') ?? []);
    }

    // To find a currency by its code
    public static function findByCode(string $code): ?self
    {
        return self::allCached()->firstWhere('code', $code);
    }

    // To find a currency by its name
    public static function findByName(string $name): ?self
    {
        return self::allCached()->firstWhere('name', $name);
    }

    // Get currency name by its code
    public static function getNameByCode(string $code): ?string
    {
        $currency = self::findByCode($code);

        return $currency->name ?? null;
    }

    // Get currency code by its name
    public static function getCodeByName(string $name): ?string
    {
        $currency = self::findByName($name);

        return $currency->code ?? null;
    }

    // Get currency symbol by its code
    public static function getSymbolByCode(string $code): ?string
    {
        $currency = self::findByCode($code);

        return $currency->symbol ?? null;
    }

    // Get currency symbol by its name
    public static function getSymbolByName(string $name): ?string
    {
        $currency = self::findByName($name);

        return $currency->symbol ?? null;
    }

    // Get currency precision by its code
    public static function getPrecisionByCode(string $code): ?int
    {
        $currency = self::findByCode($code);

        return $currency->precision ?? null;
    }

    // Get currency precision by its name
    public static function getPrecisionByName(string $name): ?int
    {
        $currency = self::findByName($name);

        return $currency->precision ?? null;
    }

    // Get currency decimal mark by its code
    public static function getDecimalMarkByCode(string $code): ?string
    {
        $currency = self::findByCode($code);

        return $currency->decimal_mark ?? null;
    }

    // Get currency decimal mark by its name
    public static function getDecimalMarkByName(string $name): ?string
    {
        $currency = self::findByName($name);

        return $currency->decimal_mark ?? null;
    }

    // Get currency thousands separator by its code
    public static function getThousandsSeparatorByCode(string $code): ?string
    {
        $currency = self::findByCode($code);

        return $currency->thousands_separator ?? null;
    }

    // Get currency thousands separator by its name
    public static function getThousandsSeparatorByName(string $name): ?string
    {
        $currency = self::findByName($name);

        return $currency->thousands_separator ?? null;
    }

    // Get currency symbol first by its code
    public static function getSymbolFirstByCode(string $code): ?bool
    {
        $currency = self::findByCode($code);

        return $currency->symbol_first ?? null;
    }

    // Get currency symbol first by its name
    public static function getSymbolFirstByName(string $name): ?bool
    {
        $currency = self::findByName($name);

        return $currency->symbol_first ?? null;
    }

    // Get currency subunit by its code
    public static function getSubunitByCode(string $code): ?int
    {
        $currency = self::findByCode($code);

        return $currency->subunit ?? null;
    }

    // Get currency subunit by its name
    public static function getSubunitByName(string $name): ?int
    {
        $currency = self::findByName($name);

        return $currency->subunit ?? null;
    }

    // Get all currency codes
    public static function getAllCodes(): Collection
    {
        return self::allCached()->pluck('code');
    }

    // Get all currency names
    public static function getAllNames(): Collection
    {
        return self::allCached()->pluck('name');
    }
}
