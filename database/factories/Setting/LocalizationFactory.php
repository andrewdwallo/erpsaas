<?php

namespace Database\Factories\Setting;

use App\Enums\Setting\DateFormat;
use App\Enums\Setting\NumberFormat;
use App\Enums\Setting\TimeFormat;
use App\Enums\Setting\WeekStart;
use App\Models\Setting\Localization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Localization>
 */
class LocalizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Localization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date_format' => DateFormat::DEFAULT,
            'time_format' => TimeFormat::DEFAULT,
        ];
    }

    public function withCountry(string $code, string $language = 'en'): Factory
    {
        $number_format = NumberFormat::fromLanguageAndCountry($language, $code) ?? NumberFormat::DEFAULT;
        $percent_first = Localization::isPercentFirst($language, $code) ?? false;

        $locale = Localization::getLocale($language, $code);
        $timezone = $this->faker->timezone($code);
        $week_start = Localization::getWeekStart($locale) ?? WeekStart::DEFAULT;

        return $this->state([
            'language' => $language,
            'timezone' => $timezone,
            'number_format' => $number_format,
            'percent_first' => $percent_first,
            'week_start' => $week_start,
            'fiscal_year_end_month' => 12,
            'fiscal_year_end_day' => 31,
        ]);
    }
}
