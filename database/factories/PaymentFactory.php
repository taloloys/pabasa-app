<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Church;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $ref = 'GCASH-'.fake()->numerify('##########');

        return [
            'public_id' => (string) Str::ulid(),
            'order_id' => Order::factory(),
            'church_id' => Church::factory(),
            'expected_amount' => '100.00',
            'submitted_amount' => '100.00',
            'verified_amount' => null,
            'currency' => 'PHP',
            'status' => PaymentStatus::PendingVerification,
            'provider' => 'gcash',
            'submitted_reference' => $ref,
            'normalized_reference' => strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $ref)),
            'verified_by' => null,
            'verified_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function pendingPayment(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::PendingPayment,
            'submitted_amount' => null,
            'submitted_reference' => null,
            'normalized_reference' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => PaymentStatus::Verified,
            'verified_amount' => $attrs['submitted_amount'] ?? '100.00',
            'verified_at' => now(),
        ]);
    }

    public function needsReview(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::NeedsReview,
        ]);
    }
}
