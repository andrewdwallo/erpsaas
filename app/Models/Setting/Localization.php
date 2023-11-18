<?php

namespace App\Models\Setting;

use App\Enums\DateFormat;
use App\Enums\NumberFormat;
use App\Enums\TimeFormat;
use App\Enums\WeekStart;
use App\Traits\Blamable;
use App\Traits\CompanyOwned;
use Database\Factories\Setting\LocalizationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NumberFormatter;
use ResourceBundle;
use Symfony\Component\Intl\Languages;
use Wallo\FilamentCompanies\FilamentCompanies;
use Wallo\Transmatic\Facades\Transmatic;

class Localization extends Model
{
    use Blamable;
    use CompanyOwned;
    use HasFactory;

    protected $table = 'localizations';

    protected $fillable = [
        'company_id',
        'language',
        'timezone',
        'date_format',
        'time_format',
        'fiscal_year_start',
        'fiscal_year_end',
        'week_start',
        'number_format',
        'percent_first',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_format' => DateFormat::class,
        'time_format' => TimeFormat::class,
        'fiscal_year_start' => 'date',
        'fiscal_year_end' => 'date',
        'week_start' => WeekStart::class,
        'number_format' => NumberFormat::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::companyModel(), 'company_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(FilamentCompanies::userModel(), 'updated_by');
    }

    public static function getLocale(string $language, string $countryCode): string
    {
        $fullLocale = "{$language}_{$countryCode}";

        if (in_array($fullLocale, ResourceBundle::getLocales(''), true)) {
            return $fullLocale;
        }

        return $language;
    }

    public static function getWeekStart(string $locale): int
    {
        $date = now()->locale($locale);

        $firstDay = $date->startOfWeek()->dayOfWeekIso;

        return WeekStart::from($firstDay)->value ?? WeekStart::DEFAULT;
    }

    public static function isPercentFirst(string $language, string $countryCode): bool
    {
        $test = 25;
        $fullLocale = "{$language}_{$countryCode}";

        $formatter = new NumberFormatter($fullLocale, NumberFormatter::PERCENT);
        $formattedPercent = $formatter->format($test);

        return strpos($formattedPercent, '%') < strpos($formattedPercent, $test);
    }

    public function getDateTimeFormatAttribute(): string
    {
        return $this->date_format . ' ' . $this->time_format;
    }

    public static function getAllLanguages(): array
    {
        return Transmatic::getSupportedLanguages();
    }

    public static function newFactory(): Factory
    {
        return LocalizationFactory::new();
    }
}
