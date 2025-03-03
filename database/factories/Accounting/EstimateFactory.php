<?php

namespace Database\Factories\Accounting;

use App\Enums\Accounting\EstimateStatus;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\Estimate;
use App\Models\Common\Client;
use App\Models\Setting\DocumentDefault;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Estimate>
 */
class EstimateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Estimate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $estimateDate = $this->faker->dateTimeBetween('-1 year');

        return [
            'company_id' => 1,
            'client_id' => Client::inRandomOrder()->value('id'),
            'header' => 'Estimate',
            'subheader' => 'Estimate',
            'estimate_number' => $this->faker->unique()->numerify('EST-####'),
            'reference_number' => $this->faker->unique()->numerify('REF-####'),
            'date' => $estimateDate,
            'expiration_date' => Carbon::parse($estimateDate)->addDays($this->faker->numberBetween(14, 30)),
            'status' => EstimateStatus::Draft,
            'currency_code' => 'USD',
            'terms' => $this->faker->sentence,
            'footer' => $this->faker->sentence,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function withLineItems(int $count = 3): static
    {
        return $this->afterCreating(function (Estimate $estimate) use ($count) {
            DocumentLineItem::factory()
                ->count($count)
                ->forEstimate($estimate)
                ->create();

            $this->recalculateTotals($estimate);
        });
    }

    public function approved(): static
    {
        return $this->afterCreating(function (Estimate $estimate) {
            $this->ensureLineItems($estimate);

            if (! $estimate->canBeApproved()) {
                return;
            }

            $approvedAt = Carbon::parse($estimate->date)
                ->addHours($this->faker->numberBetween(1, 24));

            $estimate->approveDraft($approvedAt);
        });
    }

    public function accepted(): static
    {
        return $this->afterCreating(function (Estimate $estimate) {
            $this->ensureSent($estimate);

            $acceptedAt = Carbon::parse($estimate->last_sent_at)
                ->addDays($this->faker->numberBetween(1, 7));

            $estimate->markAsAccepted($acceptedAt);
        });
    }

    public function converted(): static
    {
        return $this->afterCreating(function (Estimate $estimate) {
            if (! $estimate->wasAccepted()) {
                $this->accepted()->callAfterCreating(collect([$estimate]));
            }

            $convertedAt = Carbon::parse($estimate->accepted_at)
                ->addDays($this->faker->numberBetween(1, 7));

            $estimate->convertToInvoice($convertedAt);
        });
    }

    public function declined(): static
    {
        return $this->afterCreating(function (Estimate $estimate) {
            $this->ensureSent($estimate);

            $declinedAt = Carbon::parse($estimate->last_sent_at)
                ->addDays($this->faker->numberBetween(1, 7));

            $estimate->markAsDeclined($declinedAt);
        });
    }

    public function sent(): static
    {
        return $this->afterCreating(function (Estimate $estimate) {
            $this->ensureApproved($estimate);

            $sentAt = Carbon::parse($estimate->approved_at)
                ->addHours($this->faker->numberBetween(1, 24));

            $estimate->markAsSent($sentAt);
        });
    }

    public function viewed(): static
    {
        return $this->afterCreating(function (Estimate $estimate) {
            $this->ensureSent($estimate);

            $viewedAt = Carbon::parse($estimate->last_sent_at)
                ->addHours($this->faker->numberBetween(1, 24));

            $estimate->markAsViewed($viewedAt);
        });
    }

    public function expired(): static
    {
        return $this
            ->state([
                'expiration_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            ])
            ->afterCreating(function (Estimate $estimate) {
                $this->ensureApproved($estimate);
            });
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Estimate $estimate) {
            $this->ensureLineItems($estimate);

            $number = DocumentDefault::getBaseNumber() + $estimate->id;

            $estimate->updateQuietly([
                'estimate_number' => "EST-{$number}",
                'reference_number' => "REF-{$number}",
            ]);

            if ($estimate->wasApproved() && $estimate->is_currently_expired) {
                $estimate->updateQuietly([
                    'status' => EstimateStatus::Expired,
                ]);
            }
        });
    }

    protected function ensureLineItems(Estimate $estimate): void
    {
        if (! $estimate->hasLineItems()) {
            $this->withLineItems()->callAfterCreating(collect([$estimate]));
        }
    }

    protected function ensureApproved(Estimate $estimate): void
    {
        if (! $estimate->wasApproved()) {
            $this->approved()->callAfterCreating(collect([$estimate]));
        }
    }

    protected function ensureSent(Estimate $estimate): void
    {
        if (! $estimate->hasBeenSent()) {
            $this->sent()->callAfterCreating(collect([$estimate]));
        }
    }

    protected function recalculateTotals(Estimate $estimate): void
    {
        $estimate->refresh();

        if (! $estimate->hasLineItems()) {
            return;
        }

        $subtotal = $estimate->lineItems()->sum('subtotal') / 100;
        $taxTotal = $estimate->lineItems()->sum('tax_total') / 100;
        $discountTotal = $estimate->lineItems()->sum('discount_total') / 100;
        $grandTotal = $subtotal + $taxTotal - $discountTotal;

        $estimate->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'discount_total' => $discountTotal,
            'total' => $grandTotal,
        ]);
    }
}
