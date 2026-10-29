<?php

namespace Database\Factories;

use App\Enums\PaymentExceptionStatus;
use App\Enums\PaymentExceptionType;
use App\Models\Church;
use App\Models\Payment;
use App\Models\PaymentException;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentException>
 */
class PaymentExceptionFactory extends Factory
{
    protected $model = PaymentException::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'church_id' => Church::factory(),
            'type' => PaymentExceptionType::Underpayment,
            'status' => PaymentExceptionStatus::Open,
            'priority' => 'normal',
            'description' => 'Payment exception requiring verification review.',
            'opened_by' => User::factory(),
        ];
    }
}
