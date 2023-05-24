<?php

namespace Database\Factories;

use App\Models\Document\DocumentTotal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTotal>
 */
class DocumentTotalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DocumentTotal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
}
