<?php

namespace App\Actions\Orders;

use App\Enums\CategoryType;
use App\Enums\IntentionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\Intention;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Booking\BookingCutoffService;
use App\Services\GuestAccess\GuestAccessService;
use App\Services\Pricing\PricingResolutionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    public function __construct(
        protected BookingCutoffService $cutoffService,
        protected PricingResolutionService $pricingResolver,
        protected GuestAccessService $guestAccess,
        protected AuditService $audit,
    ) {}

    /**
     * @param  array{
     *     mass_schedule_id: int,
     *     church_id?: ?int,
     *     submitter_name: string,
     *     submitter_email: string,
     *     privacy_acknowledged: bool,
     *     privacy_notice_version?: string,
     *     intentions: array<array{
     *         category_id: int,
     *         person_or_family_name: string,
     *         wording: string,
     *         mass_schedule_id?: ?int,
     *         church_id?: ?int,
     *     }>
     * }  $data
     * @return array{order: Order, payment: Payment, guest_token: ?string, warnings: array}
     */
    public function execute(array $data, ?User $actor = null): array
    {
        // 1. Privacy notice validation
        if (empty($data['privacy_acknowledged'])) {
            throw ValidationException::withMessages([
                'privacy_acknowledged' => ['You must acknowledge the privacy notice to submit prayer intentions.'],
            ]);
        }

        $intentionsData = $data['intentions'] ?? [];
        if (empty($intentionsData)) {
            throw ValidationException::withMessages([
                'intentions' => ['At least one prayer intention must be submitted.'],
            ]);
        }

        // 2. Load and lock Mass schedule and Church from database
        $massScheduleId = (int) $data['mass_schedule_id'];
        $mass = MassSchedule::with('church')->findOrFail($massScheduleId);
        $church = $mass->church;

        // 3. Verify Church publication and Mass cutoff/availability
        $this->cutoffService->assertCanAcceptBooking($mass);

        // 4 & 5. Validate that all intentions belong to the same church and Mass, and categories are active
        $warnings = [];
        $existingIntentionHashes = [];
        $existingNames = [];

        foreach ($intentionsData as $index => $item) {
            // Rule 3: Reject intentions specifying a different mass or church
            if (isset($item['mass_schedule_id']) && (int) $item['mass_schedule_id'] !== $massScheduleId) {
                throw ValidationException::withMessages([
                    "intentions.{$index}.mass_schedule_id" => ['An order cannot contain intentions across different Masses.'],
                ]);
            }

            if (isset($item['church_id']) && (int) $item['church_id'] !== $church->id) {
                throw ValidationException::withMessages([
                    "intentions.{$index}.church_id" => ['An order cannot contain intentions across different churches.'],
                ]);
            }

            // Rule 4: Inactive category is rejected for new submissions
            $categoryId = (int) ($item['category_id'] ?? 0);
            $category = ChurchCategory::find($categoryId);

            if (! $category || ! $category->is_active || ($category->type !== CategoryType::Intention)) {
                throw ValidationException::withMessages([
                    "intentions.{$index}.category_id" => ['The selected intention category is currently inactive or not available for new submissions.'],
                ]);
            }

            // Ensure category belongs to this church or is system-wide
            if (! $category->is_system && $category->church_id !== $church->id) {
                throw ValidationException::withMessages([
                    "intentions.{$index}.category_id" => ['This category is not authorized for this parish.'],
                ]);
            }

            // Rule 6: Duplicate-warning logic flags likely duplicates without blocking legitimate repeats
            $nameKey = trim(mb_strtolower($item['person_or_family_name']));
            $exactKey = $nameKey.'|'.trim(mb_strtolower($item['wording']));
            if (in_array($exactKey, $existingIntentionHashes, true)) {
                $warnings[] = 'Intention #'.($index + 1)." ('{$item['person_or_family_name']}') is identical to another intention in this submission.";
            } elseif (in_array($nameKey, $existingNames, true)) {
                $warnings[] = "Likely duplicate intention detected in this submission for \"{$item['person_or_family_name']}\".";
            }
            $existingIntentionHashes[] = $exactKey;
            $existingNames[] = $nameKey;
        }

        // 6. Resolve approved pricing configuration (Rule 7: No invented pricing)
        $pricing = $this->pricingResolver->resolve($church, $intentionsData);

        $submitterType = $actor ? 'registered_user' : 'guest';
        $submitterName = $actor ? $actor->name : trim($data['submitter_name'] ?? '');
        $submitterEmail = strtolower(trim($actor ? $actor->email : ($data['submitter_email'] ?? '')));

        if (empty($submitterName) || empty($submitterEmail)) {
            throw ValidationException::withMessages([
                'submitter_name' => ['Contact name and email are required.'],
            ]);
        }

        // 7 & 8. Database transaction for Order, Intentions, Payment, and Guest Credential
        return DB::transaction(function () use (
            $church,
            $mass,
            $actor,
            $submitterType,
            $submitterName,
            $submitterEmail,
            $data,
            $intentionsData,
            $pricing,
            $warnings
        ) {
            // Recheck cutoff inside transaction immediately before creation (concurrency boundary check)
            $this->cutoffService->assertCanAcceptBooking($mass->id, lock: true);

            $orderNumber = 'ORD-'.strtoupper(Str::random(8));

            $order = Order::create([
                'public_id' => (string) Str::ulid(),
                'order_number' => $orderNumber,
                'user_id' => $actor?->id,
                'submitter_type' => $submitterType,
                'church_id' => $church->id,
                'mass_schedule_id' => $mass->id,
                'status' => OrderStatus::AwaitingPayment,
                'currency' => $pricing['currency'],
                'expected_amount' => $pricing['total_amount'],
                'pricing_configuration_id' => $pricing['configuration']->id,
                'pricing_snapshot' => $pricing['snapshot'],
                'submitter_name' => $submitterName,
                'submitter_email' => $submitterEmail,
                'privacy_notice_version' => $data['privacy_notice_version'] ?? '1.0',
                'privacy_acknowledged_at' => now(),
                'submitted_at' => now(),
            ]);

            // Create intention records, preserving exact submitted Unicode characters
            foreach ($intentionsData as $idx => $item) {
                $category = ChurchCategory::find($item['category_id']);

                Intention::create([
                    'order_id' => $order->id,
                    'church_id' => $church->id,
                    'mass_schedule_id' => $mass->id,
                    'category_id' => $category?->id,
                    'category_label_snapshot' => $category?->label,
                    'person_or_family_name' => $item['person_or_family_name'],
                    'wording' => $item['wording'],
                    'status' => IntentionStatus::AwaitingPayment,
                    'original_name_snapshot' => $item['person_or_family_name'],
                    'original_wording_snapshot' => $item['wording'],
                    'sort_order' => $idx + 1,
                ]);
            }

            // Create initial Payment record
            $payment = Payment::create([
                'public_id' => (string) Str::ulid(),
                'order_id' => $order->id,
                'church_id' => $church->id,
                'expected_amount' => $pricing['total_amount'],
                'submitted_amount' => null,
                'verified_amount' => null,
                'currency' => $pricing['currency'],
                'status' => PaymentStatus::PendingPayment,
                'provider' => 'gcash',
            ]);

            // Issue guest credentials if guest submitter
            $rawGuestToken = null;
            if ($submitterType === 'guest') {
                $guestCred = $this->guestAccess->issueCredential($order, $submitterEmail);
                $rawGuestToken = $guestCred['raw_token'];
            }

            // Audit order submission (passwords/tokens omitted)
            $this->audit->record(
                action: 'order.created',
                target: $order,
                churchId: $church->id,
                reason: "Order {$orderNumber} submitted with ".count($intentionsData).' intention(s).',
                afterData: [
                    'order_number' => $orderNumber,
                    'expected_amount' => $pricing['total_amount'],
                    'submitter_type' => $submitterType,
                    'intentions_count' => count($intentionsData),
                ],
                actorUserId: $actor?->id,
                actorType: $submitterType,
            );

            return [
                'order' => $order,
                'payment' => $payment,
                'guest_token' => $rawGuestToken,
                'warnings' => $warnings,
            ];
        });
    }
}
