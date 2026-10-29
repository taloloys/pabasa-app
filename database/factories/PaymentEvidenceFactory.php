<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentEvidence>
 */
class PaymentEvidenceFactory extends Factory
{
    protected $model = PaymentEvidence::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'church_id' => Church::factory(),
            'storage_disk' => 'private',
            'storage_key' => 'evidence/'.Str::uuid().'.jpg',
            'original_filename' => 'gcash_screenshot.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 102400,
            'checksum' => hash('sha256', Str::random(20)),
            'uploaded_by_user_id' => User::factory(),
            'uploaded_by_guest' => false,
            'evidence_status' => 'active',
        ];
    }
}
