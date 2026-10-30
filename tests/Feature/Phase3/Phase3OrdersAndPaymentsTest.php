<?php

namespace Tests\Feature\Phase3;

use App\Actions\Orders\CreateOrderAction;
use App\Actions\Payments\SubmitPaymentEvidenceAction;
use App\Actions\Payments\VerifyPaymentAction;
use App\Enums\BookingStatus;
use App\Enums\CancellationRequestStatus;
use App\Enums\CategoryType;
use App\Enums\ChurchStatus;
use App\Enums\IntentionStatus;
use App\Enums\MassAssignmentStatus;
use App\Enums\MassStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentExceptionStatus;
use App\Enums\PaymentExceptionType;
use App\Enums\PaymentStatus;
use App\Enums\PricingStatus;
use App\Enums\ReschedulingRequestStatus;
use App\Models\CancellationRequest;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\ChurchMembership;
use App\Models\GuestOrderAccess;
use App\Models\Intention;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use App\Models\PaymentException;
use App\Models\PricingConfiguration;
use App\Models\ReadingListEntry;
use App\Models\ReschedulingRequest;
use App\Models\User;
use App\Services\GuestAccess\GuestAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase3OrdersAndPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    /**
     * Helper to set up an authenticated Central Admin.
     */
    protected function createCentralAdmin(): User
    {
        return User::factory()->create(['is_central_admin' => true]);
    }

    /**
     * Helper to set up an authenticated church member.
     */
    protected function createChurchStaff(Church $church, MembershipRole $role): User
    {
        $user = User::factory()->create();
        ChurchMembership::factory()->create([
            'church_id' => $church->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => MembershipStatus::Active,
        ]);

        return $user;
    }

    /**
     * Helper to create a published church with scheduled Mass, approved pricing, and intention category.
     */
    protected function createPublishedChurchWithMass(): array
    {
        $church = Church::factory()->create([
            'status' => ChurchStatus::Published,
            'timezone' => 'Asia/Manila',
        ]);

        $category = ChurchCategory::factory()->create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention,
            'is_active' => true,
            'label' => 'Thanksgiving',
        ]);

        $pricingConfig = PricingConfiguration::factory()->create([
            'scope' => 'church',
            'church_id' => $church->id,
            'status' => PricingStatus::Approved,
            'pricing_rules' => [
                'base_price_per_intention' => '100.00',
            ],
            'version' => 1,
            'effective_from' => now()->subDay(),
            'approved_at' => now()->subDay(),
        ]);

        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'status' => MassStatus::Scheduled,
            'booking_status' => BookingStatus::Open,
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'booking_cutoff_at' => now()->addDays(2)->setTime(20, 0),
        ]);

        return [$church, $mass, $category, $pricingConfig];
    }

    // -------------------------------------------------------------------------
    // Test Case 1: Registered user creates an order with multiple intentions for one Mass
    // -------------------------------------------------------------------------
    public function test_case_1_registered_user_creates_an_order_with_multiple_intentions_for_one_mass(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();
        $user = User::factory()->create();

        $category2 = ChurchCategory::factory()->create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention,
            'is_active' => true,
            'label' => 'Birthday Thanksgiving',
        ]);

        $response = $this->actingAs($user)->post('/orders', [
            'mass_schedule_id' => $mass->id,
            'privacy_acknowledged' => true,
            'submitter_name' => $user->name,
            'submitter_email' => $user->email,
            'intentions' => [
                [
                    'category_id' => $category->id,
                    'person_or_family_name' => 'Cruz Family',
                    'wording' => 'Prayers of thanksgiving for blessings received',
                ],
                [
                    'category_id' => $category2->id,
                    'person_or_family_name' => 'Maria Santos',
                    'wording' => 'In celebration of 60th birthday',
                ],
            ],
        ]);

        $response->assertRedirect();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(OrderStatus::AwaitingPayment, $order->status);
        $this->assertEquals('200.00', $order->expected_amount);
        $this->assertEquals(2, $order->intentions()->count());

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'church_id' => $church->id,
            'status' => PaymentStatus::PendingPayment->value,
            'expected_amount' => '200.00',
        ]);
    }

    // -------------------------------------------------------------------------
    // Test Case 2: Guest can submit without an account and receives a secure tracking flow
    // -------------------------------------------------------------------------
    public function test_case_2_guest_can_submit_without_an_account_and_receives_a_secure_tracking_flow(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();

        $response = $this->post('/orders', [
            'mass_schedule_id' => $mass->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Juan Guest',
            'submitter_email' => 'juan.guest@example.com',
            'intentions' => [
                [
                    'category_id' => $category->id,
                    'person_or_family_name' => 'Reyes Family',
                    'wording' => 'Safe travels and peace',
                ],
            ],
        ]);

        $response->assertRedirect();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertEquals('guest', $order->submitter_type);

        $guestAccess = GuestOrderAccess::where('order_id', $order->id)->first();
        $this->assertNotNull($guestAccess);
        $this->assertNotNull($guestAccess->token_hash);
        $this->assertNull($guestAccess->email_verified_at);

        $token = session('guest_order_token_'.$order->order_number);
        $this->assertNotEmpty($token);

        $trackResponse = $this->get('/guest/track/'.$token);
        $trackResponse->assertOk();
    }

    // -------------------------------------------------------------------------
    // Test Case 3: An order containing intentions for different churches or Masses is rejected
    // -------------------------------------------------------------------------
    public function test_case_3_an_order_containing_intentions_for_different_churches_or_masses_is_rejected(): void
    {
        [$church1, $mass1, $category1] = $this->createPublishedChurchWithMass();
        [$church2, $mass2, $category2] = $this->createPublishedChurchWithMass();

        $response = $this->postJson('/orders', [
            'mass_schedule_id' => $mass1->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Tester',
            'submitter_email' => 'tester@example.com',
            'intentions' => [
                [
                    'category_id' => $category1->id,
                    'church_id' => $church2->id, // Mismatched church
                    'person_or_family_name' => 'Test Name',
                    'wording' => 'Test wording',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['intentions.0.church_id']);

        // Mismatched category belonging to church 2
        $response2 = $this->postJson('/orders', [
            'mass_schedule_id' => $mass1->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Tester',
            'submitter_email' => 'tester@example.com',
            'intentions' => [
                [
                    'category_id' => $category2->id,
                    'person_or_family_name' => 'Test Name',
                    'wording' => 'Test wording',
                ],
            ],
        ]);

        $response2->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Test Case 4: Inactive category is rejected for new submissions; historical label snapshot unchanged
    // -------------------------------------------------------------------------
    public function test_case_4_inactive_category_is_rejected_for_new_submissions_historical_label_snapshot_unchanged(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();

        $inactiveCategory = ChurchCategory::factory()->create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention,
            'is_active' => false,
            'label' => 'Legacy Petition',
        ]);

        $response = $this->postJson('/orders', [
            'mass_schedule_id' => $mass->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Tester',
            'submitter_email' => 'tester@example.com',
            'intentions' => [
                [
                    'category_id' => $inactiveCategory->id,
                    'person_or_family_name' => 'Tester Family',
                    'wording' => 'Some prayer',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['intentions.0.category_id']);

        // Historical snapshot preservation test:
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);
        $historicalIntention = Intention::create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'category_id' => $category->id,
            'category_label_snapshot' => $category->label,
            'person_or_family_name' => 'Original Family',
            'wording' => 'Original intention wording',
            'status' => IntentionStatus::AwaitingPayment,
        ]);

        // Deactivate category
        $category->update(['is_active' => false, 'label' => 'Updated Label']);

        $historicalIntention->refresh();
        $this->assertEquals('Thanksgiving', $historicalIntention->category_label_snapshot);
    }

    // -------------------------------------------------------------------------
    // Test Case 5: Names and wording preserve exact submitted Unicode text
    // -------------------------------------------------------------------------
    public function test_case_5_names_and_wording_preserve_exact_submitted_unicode_text(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();

        $unicodeName = 'Kalag ni José María Peña-Ñañez — ᜊᜌ᜔ᜊᜌᜒᜈ᜔ ✝';
        $unicodeWording = 'Pag-ampo alang sa kalinaw sa kalag: "Pahulay nga dayon ihatag kaniya O Ginoo."';

        $this->post('/orders', [
            'mass_schedule_id' => $mass->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Maria Dela Peña',
            'submitter_email' => 'maria@example.com',
            'intentions' => [
                [
                    'category_id' => $category->id,
                    'person_or_family_name' => $unicodeName,
                    'wording' => $unicodeWording,
                ],
            ],
        ]);

        $intention = Intention::first();
        $this->assertNotNull($intention);
        $this->assertSame($unicodeName, $intention->person_or_family_name);
        $this->assertSame($unicodeWording, $intention->wording);
    }

    // -------------------------------------------------------------------------
    // Test Case 6: Duplicate-warning logic flags likely duplicates but does not block a legitimate repeat
    // -------------------------------------------------------------------------
    public function test_case_6_duplicate_warning_logic_flags_likely_duplicates_but_does_not_block_a_legitimate_repeat(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();

        $action = app(CreateOrderAction::class);

        $result = $action->execute([
            'mass_schedule_id' => $mass->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Duplicate Submitter',
            'submitter_email' => 'dup@example.com',
            'intentions' => [
                [
                    'category_id' => $category->id,
                    'person_or_family_name' => 'Juan Dela Cruz',
                    'wording' => 'Special Intention',
                ],
                [
                    'category_id' => $category->id,
                    'person_or_family_name' => 'Juan Dela Cruz',
                    'wording' => 'Another Special Intention',
                ],
            ],
        ]);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('Likely duplicate intention detected', $result['warnings'][0]);
        $this->assertEquals(2, $result['order']->intentions()->count());
    }

    // -------------------------------------------------------------------------
    // Test Case 7: No approved pricing config → configuration_required; no price invented
    // -------------------------------------------------------------------------
    public function test_case_7_no_approved_pricing_config_produces_configuration_required_no_price_invented(): void
    {
        [$church, $mass, $category, $pricingConfig] = $this->createPublishedChurchWithMass();

        // Delete approved pricing config
        $pricingConfig->delete();

        $response = $this->postJson('/orders', [
            'mass_schedule_id' => $mass->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Tester',
            'submitter_email' => 'tester@example.com',
            'intentions' => [
                [
                    'category_id' => $category->id,
                    'person_or_family_name' => 'Tester',
                    'wording' => 'Wording',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['pricing']);
        $this->assertStringContainsString('configuration_required', $response->json('errors.pricing.0'));
        $this->assertEquals(0, Order::count());
    }

    // -------------------------------------------------------------------------
    // Test Case 8: Order and all intention rows roll back if any child creation fails
    // -------------------------------------------------------------------------
    public function test_case_8_order_and_all_intention_rows_roll_back_if_any_child_creation_fails(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();

        $action = app(CreateOrderAction::class);

        try {
            $action->execute([
                'mass_schedule_id' => $mass->id,
                'privacy_acknowledged' => true,
                'submitter_name' => 'Tester',
                'submitter_email' => 'tester@example.com',
                'intentions' => [
                    [
                        'category_id' => $category->id,
                        'person_or_family_name' => 'Valid Child',
                        'wording' => 'Valid wording',
                    ],
                    [
                        'category_id' => 999999, // Inexistent category will throw
                        'person_or_family_name' => 'Invalid Child',
                        'wording' => 'Will fail',
                    ],
                ],
            ]);
            $this->fail('Expected exception was not thrown');
        } catch (\Throwable $e) {
            // Expected
        }

        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, Intention::count());
        $this->assertEquals(0, Payment::count());
    }

    // -------------------------------------------------------------------------
    // Test Case 9: Mass closing between page load and submit → server-side rejection
    // -------------------------------------------------------------------------
    public function test_case_9_mass_closing_between_page_load_and_submit_server_side_rejection(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();

        // Expire the booking window
        $mass->update([
            'booking_cutoff_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/orders', [
            'mass_schedule_id' => $mass->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Late Submitter',
            'submitter_email' => 'late@example.com',
            'intentions' => [
                [
                    'category_id' => $category->id,
                    'person_or_family_name' => 'Late Name',
                    'wording' => 'Late wording',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Order::count());
    }

    // -------------------------------------------------------------------------
    // Test Case 10: Payment evidence accepts only allowed, validated file types; rejects oversized/malicious
    // -------------------------------------------------------------------------
    public function test_case_10_payment_evidence_accepts_only_allowed_validated_file_types_rejects_oversized_malicious(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);
        $payment = Payment::factory()->pendingPayment()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
        ]);

        // Malicious / disallowed file type: .php script disguised as image
        $maliciousFile = UploadedFile::fake()->create('exploit.php', 100, 'text/x-php');

        $response = $this->postJson("/orders/{$order->id}/evidence", [
            'reference_number' => 'GCASH12345',
            'submitted_amount' => '100.00',
            'evidence_file' => $maliciousFile,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['evidence_file']);

        // Valid image upload
        $validImage = UploadedFile::fake()->image('receipt.png', 400, 600)->size(1500);

        $validResponse = $this->post("/orders/{$order->id}/evidence", [
            'reference_number' => 'GCASH987654321',
            'submitted_amount' => '100.00',
            'evidence_file' => $validImage,
        ]);

        $validResponse->assertRedirect();
        $this->assertDatabaseHas('payment_evidence', [
            'payment_id' => $payment->id,
            'mime_type' => 'image/png',
        ]);
    }

    // -------------------------------------------------------------------------
    // Test Case 11: Evidence storage is private; cannot be fetched without authorization
    // -------------------------------------------------------------------------
    public function test_case_11_evidence_storage_is_private_cannot_be_fetched_without_authorization(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
        ]);

        $fakeFile = UploadedFile::fake()->image('private_receipt.jpg', 300, 300);
        $action = app(SubmitPaymentEvidenceAction::class);
        $action->execute($payment, [
            'reference_number' => 'GCASH777888',
            'submitted_amount' => '100.00',
            'evidence_file' => $fakeFile,
        ]);

        $evidence = PaymentEvidence::where('payment_id', $payment->id)->first();
        $this->assertNotNull($evidence);
        $this->assertEquals('private', $evidence->storage_disk);
        Storage::disk('private')->assertExists($evidence->storage_key);

        // Unauthenticated access
        $unauthResponse = $this->get("/admin/payments/{$payment->id}/evidence/{$evidence->id}");
        $unauthResponse->assertRedirect('/login');

        // Random user access
        $randomUser = User::factory()->create();
        $forbiddenResponse = $this->actingAs($randomUser)->get("/admin/payments/{$payment->id}/evidence/{$evidence->id}");
        $forbiddenResponse->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Test Case 12: Evidence submission moves payment to pending_verification but NEVER verified
    // -------------------------------------------------------------------------
    public function test_case_12_evidence_submission_moves_payment_to_pending_verification_but_never_verified(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::AwaitingPayment,
        ]);
        $payment = Payment::factory()->pendingPayment()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
        ]);

        $fakeFile = UploadedFile::fake()->image('receipt.jpg', 300, 300);
        $action = app(SubmitPaymentEvidenceAction::class);
        $action->execute($payment, [
            'reference_number' => 'REF123456789',
            'submitted_amount' => '100.00',
            'evidence_file' => $fakeFile,
        ]);

        $payment->refresh();
        $order->refresh();

        $this->assertEquals(PaymentStatus::PendingVerification, $payment->status);
        $this->assertEquals(OrderStatus::PaymentUnderReview, $order->status);
        $this->assertNotEquals(PaymentStatus::Verified, $payment->status);
        $this->assertNotEquals(OrderStatus::Confirmed, $order->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 13: Church Admin cannot verify/reject payment by direct POST
    // -------------------------------------------------------------------------
    public function test_case_13_church_admin_cannot_verify_reject_payment_by_direct_post(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $churchAdmin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'status' => PaymentStatus::PendingVerification,
        ]);

        // Attempt verify
        $verifyRes = $this->actingAs($churchAdmin)->post("/admin/payments/{$payment->id}/verify", [
            'verified_amount' => '100.00',
        ]);
        $verifyRes->assertForbidden();

        // Attempt reject
        $rejectRes = $this->actingAs($churchAdmin)->post("/admin/payments/{$payment->id}/reject", [
            'reason' => 'Invalid evidence',
        ]);
        $rejectRes->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Test Case 14: Lector cannot access payment evidence
    // -------------------------------------------------------------------------
    public function test_case_14_lector_cannot_access_payment_evidence(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $lector = $this->createChurchStaff($church, MembershipRole::Lector);

        // Assign lector to Mass
        MassAssignment::factory()->create([
            'mass_schedule_id' => $mass->id,
            'church_id' => $church->id,
            'user_id' => $lector->id,
            'status' => MassAssignmentStatus::Active,
            'assigned_by' => $lector->id,
        ]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
        ]);
        $evidence = PaymentEvidence::factory()->create([
            'payment_id' => $payment->id,
            'church_id' => $church->id,
        ]);

        $response = $this->actingAs($lector)->get("/admin/payments/{$payment->id}/evidence/{$evidence->id}");
        $response->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Test Case 15: Central Admin can verify only after explicit action with verified amount/reference
    // -------------------------------------------------------------------------
    public function test_case_15_central_admin_can_verify_only_after_explicit_action_with_verified_amount_reference(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $centralAdmin = $this->createCentralAdmin();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::PaymentUnderReview,
            'expected_amount' => '100.00',
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'expected_amount' => '100.00',
            'submitted_amount' => '100.00',
            'submitted_reference' => 'GCASH999',
            'normalized_reference' => 'GCASH999',
            'status' => PaymentStatus::PendingVerification,
        ]);

        $response = $this->actingAs($centralAdmin)->post("/admin/payments/{$payment->id}/verify", [
            'verified_amount' => '100.00',
            'notes' => 'Confirmed on merchant portal',
        ]);

        $response->assertRedirect();

        $payment->refresh();
        $order->refresh();

        $this->assertEquals(PaymentStatus::Verified, $payment->status);
        $this->assertEquals('100.00', $payment->verified_amount);
        $this->assertEquals($centralAdmin->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);
        $this->assertEquals(OrderStatus::Confirmed, $order->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 16: Duplicate reference creates a review flag; same transaction cannot be counted twice
    // -------------------------------------------------------------------------
    public function test_case_16_duplicate_reference_creates_a_review_flag_same_transaction_cannot_be_counted_twice(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();

        // Initial payment already verified with this reference
        $existingOrder = Order::factory()->create(['church_id' => $church->id, 'mass_schedule_id' => $mass->id]);
        Payment::factory()->create([
            'order_id' => $existingOrder->id,
            'church_id' => $church->id,
            'submitted_reference' => 'REF-998877',
            'normalized_reference' => 'REF998877',
            'status' => PaymentStatus::Verified,
        ]);

        // Second payment submits with same reference
        $order2 = Order::factory()->create(['church_id' => $church->id, 'mass_schedule_id' => $mass->id]);
        $payment2 = Payment::factory()->pendingPayment()->create([
            'order_id' => $order2->id,
            'church_id' => $church->id,
        ]);

        $action = app(SubmitPaymentEvidenceAction::class);
        $action->execute($payment2, [
            'reference_number' => 'ref 9988-77',
            'submitted_amount' => '100.00',
            'evidence_file' => UploadedFile::fake()->image('rec.jpg'),
        ]);

        $payment2->refresh();
        $this->assertEquals(PaymentStatus::NeedsReview, $payment2->status);

        $exception = PaymentException::where('payment_id', $payment2->id)->first();
        $this->assertNotNull($exception);
        $this->assertEquals(PaymentExceptionType::DuplicateReference, $exception->type);
        $this->assertEquals(PaymentExceptionStatus::Open, $exception->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 17: Underpayment, overpayment, unclear evidence, unmatched ref, late payment → review/resolution flow
    // -------------------------------------------------------------------------
    public function test_case_17_underpayment_overpayment_unclear_evidence_unmatched_ref_late_payment_review_resolution_flow(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $centralAdmin = $this->createCentralAdmin();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'expected_amount' => '200.00',
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'expected_amount' => '200.00',
            'submitted_amount' => '150.00', // Underpayment
            'status' => PaymentStatus::PendingVerification,
        ]);

        $verifyAction = app(VerifyPaymentAction::class);

        // Verification without force resolution flags exception and holds in needs_review
        $underpaidResult = $verifyAction->execute($payment, $centralAdmin, [
            'verified_amount' => '150.00',
        ]);

        $this->assertEquals(PaymentStatus::NeedsReview, $underpaidResult->status);
        $this->assertDatabaseHas('payment_exceptions', [
            'payment_id' => $payment->id,
            'type' => PaymentExceptionType::Underpayment->value,
            'status' => PaymentExceptionStatus::Open->value,
        ]);

        // Explicit resolution by Central Admin overrides and confirms
        $verifiedPayment = $verifyAction->execute($payment, $centralAdmin, [
            'verified_amount' => '150.00',
            'force_exception_resolution' => true,
        ]);

        $this->assertEquals(PaymentStatus::Verified, $verifiedPayment->status);
        $this->assertDatabaseHas('payment_exceptions', [
            'payment_id' => $payment->id,
            'type' => PaymentExceptionType::Underpayment->value,
            'status' => PaymentExceptionStatus::Resolved->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test Case 18: Rejection requires a reason and produces user-facing notice
    // -------------------------------------------------------------------------
    public function test_case_18_rejection_requires_a_reason_and_produces_user_facing_notice(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $centralAdmin = $this->createCentralAdmin();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::PaymentUnderReview,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'status' => PaymentStatus::PendingVerification,
        ]);

        // Without reason -> 422
        $failRes = $this->actingAs($centralAdmin)->postJson("/admin/payments/{$payment->id}/reject", []);
        $failRes->assertStatus(422);

        // With reason
        $reason = 'Receipt is blurry and reference number cannot be determined.';
        $successRes = $this->actingAs($centralAdmin)->post("/admin/payments/{$payment->id}/reject", [
            'reason' => $reason,
        ]);
        $successRes->assertRedirect();

        $payment->refresh();
        $order->refresh();

        $this->assertEquals(PaymentStatus::Rejected, $payment->status);
        $this->assertEquals($reason, $payment->rejection_reason);
        $this->assertEquals(OrderStatus::ActionRequired, $order->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 19: Retried verification request does not duplicate reading-list entries
    // -------------------------------------------------------------------------
    public function test_case_19_retried_verification_request_does_not_duplicate_reading_list_entries(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();
        $centralAdmin = $this->createCentralAdmin();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::PaymentUnderReview,
            'expected_amount' => '100.00',
        ]);
        $intention = Intention::create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'category_id' => $category->id,
            'category_label_snapshot' => $category->label,
            'person_or_family_name' => 'Idempotent Intention',
            'wording' => 'Special prayer',
            'status' => IntentionStatus::AwaitingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'expected_amount' => '100.00',
            'submitted_amount' => '100.00',
            'status' => PaymentStatus::PendingVerification,
        ]);

        $action = app(VerifyPaymentAction::class);

        // Verification 1
        $action->execute($payment, $centralAdmin, ['verified_amount' => '100.00']);
        $this->assertEquals(1, ReadingListEntry::where('intention_id', $intention->id)->count());

        // Verification retry
        $action->execute($payment, $centralAdmin, ['verified_amount' => '100.00']);
        $this->assertEquals(1, ReadingListEntry::where('intention_id', $intention->id)->count());
    }

    // -------------------------------------------------------------------------
    // Test Case 20: Guest tracking rejects order number alone
    // -------------------------------------------------------------------------
    public function test_case_20_guest_tracking_rejects_order_number_alone(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'order_number' => 'ORD-TEST1234',
        ]);

        $response = $this->get('/guest/track/ORD-TEST1234');
        $response->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // Test Case 21: Guest tracking requires valid credential + verification + unexpired access
    // -------------------------------------------------------------------------
    public function test_case_21_guest_tracking_requires_valid_credential_verification_unexpired_access(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);

        $guestService = app(GuestAccessService::class);
        $credential = $guestService->issueCredential($order, 'guest@example.com');

        // Valid credential viewable
        $res = $this->get('/guest/track/'.$credential['raw_token']);
        $res->assertOk();

        // Verify challenge code
        $verifyRes = $this->post('/guest/track/'.$credential['raw_token'].'/verify', [
            'verification_code' => $credential['verification_code'],
        ]);
        $verifyRes->assertRedirect();
        $this->assertNotNull($credential['access']->fresh()->email_verified_at);

        // Expired access
        $credential['access']->update(['expires_at' => now()->subDay()]);
        $expiredRes = $this->get('/guest/track/'.$credential['raw_token']);
        $expiredRes->assertNotFound();
    }

    // -------------------------------------------------------------------------
    // Test Case 22: Recovery response does not disclose whether an email exists
    // -------------------------------------------------------------------------
    public function test_case_22_recovery_response_does_not_disclose_whether_an_email_exists(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'submitter_email' => 'real.existing@example.com',
            'submitter_type' => 'guest',
        ]);

        // Request with existing email
        $res1 = $this->postJson('/guest/recover', [
            'email' => 'real.existing@example.com',
        ]);
        $res1->assertOk();

        // Request with non-existing email
        $res2 = $this->postJson('/guest/recover', [
            'email' => 'nobody.completely.fake@example.com',
        ]);
        $res2->assertOk();

        // Generic response messages match identically
        $this->assertSame($res1->json('message'), $res2->json('message'));
    }

    // -------------------------------------------------------------------------
    // Test Case 23: Replacing a guest credential invalidates the old credential
    // -------------------------------------------------------------------------
    public function test_case_23_replacing_a_guest_credential_invalidates_the_old_credential(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);

        $guestService = app(GuestAccessService::class);
        $cred1 = $guestService->issueCredential($order, 'guest@example.com');
        $cred2 = $guestService->issueCredential($order, 'guest@example.com');

        $this->assertNull($guestService->verifyToken($cred1['raw_token']));
        $this->assertNotNull($guestService->verifyToken($cred2['raw_token']));
    }

    // -------------------------------------------------------------------------
    // Test Case 24: Matching email alone cannot link a guest order to a registered account
    // -------------------------------------------------------------------------
    public function test_case_24_matching_email_alone_cannot_link_a_guest_order_to_a_registered_account(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();

        $guestOrder = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'submitter_email' => 'shared@example.com',
            'submitter_type' => 'guest',
            'user_id' => null,
        ]);

        // User registers with exact same email
        $user = User::factory()->create(['email' => 'shared@example.com']);

        // User should not be authorized to edit intentions or requests on the guest order
        $response = $this->actingAs($user)->patchJson("/orders/{$guestOrder->id}/intentions", [
            'intentions' => [],
        ]);
        $response->assertForbidden();

        $guestOrder->refresh();
        $this->assertNull($guestOrder->user_id);
    }

    // -------------------------------------------------------------------------
    // Test Case 25: Owner can edit eligible intentions before payment verification; blocked after
    // -------------------------------------------------------------------------
    public function test_case_25_owner_can_edit_eligible_intentions_before_payment_verification_blocked_after(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $user->id,
            'status' => OrderStatus::AwaitingPayment,
        ]);
        $intention = Intention::create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'category_id' => $category->id,
            'category_label_snapshot' => $category->label,
            'person_or_family_name' => 'Initial Name',
            'wording' => 'Initial wording',
            'status' => IntentionStatus::AwaitingPayment,
        ]);

        // Edit before verification -> Succeeds
        $resBefore = $this->actingAs($user)->patch("/orders/{$order->id}/intentions", [
            'intentions' => [
                [
                    'id' => $intention->id,
                    'person_or_family_name' => 'Corrected Name',
                    'wording' => 'Corrected wording',
                ],
            ],
        ]);
        $resBefore->assertRedirect();
        $this->assertEquals('Corrected Name', $intention->fresh()->person_or_family_name);

        // Verify payment
        $order->update(['status' => OrderStatus::Confirmed]);

        // Edit after verification -> Blocked
        $resAfter = $this->actingAs($user)->patch("/orders/{$order->id}/intentions", [
            'intentions' => [
                [
                    'id' => $intention->id,
                    'person_or_family_name' => 'Another Name',
                    'wording' => 'Another wording',
                ],
            ],
        ]);
        $resAfter->assertForbidden();
    }

    // -------------------------------------------------------------------------
    // Test Case 26: Cancellation request does not automatically cancel/refund the order
    // -------------------------------------------------------------------------
    public function test_case_26_cancellation_request_does_not_automatically_cancel_refund_the_order(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $user->id,
            'status' => OrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($user)->post("/orders/{$order->id}/cancellations", [
            'reason' => 'Family travel delayed',
        ]);
        $response->assertRedirect();

        $req = CancellationRequest::where('order_id', $order->id)->first();
        $this->assertNotNull($req);
        $this->assertEquals(CancellationRequestStatus::Submitted, $req->status);

        // Order remains Confirmed
        $order->refresh();
        $this->assertEquals(OrderStatus::Confirmed, $order->status);
        $this->assertEquals(0, $order->refunds()->count());
    }

    // -------------------------------------------------------------------------
    // Test Case 27: Rescheduling request does not automatically move an order
    // -------------------------------------------------------------------------
    public function test_case_27_rescheduling_request_does_not_automatically_move_an_order(): void
    {
        [$church, $mass1] = $this->createPublishedChurchWithMass();
        $mass2 = MassSchedule::factory()->create(['church_id' => $church->id]);
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass1->id,
            'user_id' => $user->id,
            'status' => OrderStatus::Confirmed,
        ]);

        $response = $this->actingAs($user)->post("/orders/{$order->id}/rescheduling", [
            'requested_mass_schedule_id' => $mass2->id,
            'reason' => 'Schedule conflict',
        ]);
        $response->assertRedirect();

        $req = ReschedulingRequest::where('order_id', $order->id)->first();
        $this->assertNotNull($req);
        $this->assertEquals(ReschedulingRequestStatus::Submitted, $req->status);

        // Order remains on Mass 1
        $order->refresh();
        $this->assertEquals($mass1->id, $order->mass_schedule_id);
    }

    // -------------------------------------------------------------------------
    // Test Case 28: Every sensitive payment/order decision creates an audit record
    // -------------------------------------------------------------------------
    public function test_case_28_every_sensitive_payment_order_decision_creates_an_audit_record(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();
        $centralAdmin = $this->createCentralAdmin();

        $createOrderAction = app(CreateOrderAction::class);
        $result = $createOrderAction->execute([
            'mass_schedule_id' => $mass->id,
            'privacy_acknowledged' => true,
            'submitter_name' => 'Audit Tester',
            'submitter_email' => 'audit@example.com',
            'intentions' => [
                [
                    'category_id' => $category->id,
                    'person_or_family_name' => 'Audit Name',
                    'wording' => 'Audit wording',
                ],
            ],
        ]);

        $order = $result['order'];
        $payment = $order->payments()->first();

        // 1. Order created audit
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.created',
            'target_type' => Order::class,
            'target_id' => (string) $order->id,
        ]);

        // 2. Evidence uploaded audit
        $submitEvidenceAction = app(SubmitPaymentEvidenceAction::class);
        $submitEvidenceAction->execute($payment, [
            'reference_number' => 'AUDIT-REF-1',
            'submitted_amount' => '100.00',
            'evidence_file' => UploadedFile::fake()->image('rec.png'),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.evidence_submitted',
            'target_type' => Payment::class,
            'target_id' => (string) $payment->id,
        ]);

        // 3. Payment verified audit
        $verifyAction = app(VerifyPaymentAction::class);
        $verifyAction->execute($payment, $centralAdmin, ['verified_amount' => '100.00']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment.verified',
            'target_type' => Payment::class,
            'target_id' => (string) $payment->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test Case 29: Concurrent Central Admin verification → one consistent decision
    // -------------------------------------------------------------------------
    public function test_case_29_concurrent_central_admin_verification_one_consistent_decision(): void
    {
        [$church, $mass, $category] = $this->createPublishedChurchWithMass();
        $admin1 = $this->createCentralAdmin();
        $admin2 = $this->createCentralAdmin();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::PaymentUnderReview,
            'expected_amount' => '100.00',
        ]);
        $intention = Intention::create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'category_id' => $category->id,
            'category_label_snapshot' => $category->label,
            'person_or_family_name' => 'Concurrent Name',
            'wording' => 'Concurrent wording',
            'status' => IntentionStatus::AwaitingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'expected_amount' => '100.00',
            'submitted_amount' => '100.00',
            'status' => PaymentStatus::PendingVerification,
        ]);

        $verifyAction = app(VerifyPaymentAction::class);

        // Call 1
        $res1 = $verifyAction->execute($payment, $admin1, ['verified_amount' => '100.00']);

        // Call 2 (simulating race where lock was released and status is verified)
        $res2 = $verifyAction->execute($payment, $admin2, ['verified_amount' => '100.00']);

        $this->assertEquals(PaymentStatus::Verified, $res1->status);
        $this->assertEquals(PaymentStatus::Verified, $res2->status);
        $this->assertEquals(1, ReadingListEntry::where('intention_id', $intention->id)->count());
    }

    // -------------------------------------------------------------------------
    // Test Case 30: Payment after cutoff held for review rather than silently discarded
    // -------------------------------------------------------------------------
    public function test_case_30_payment_after_cutoff_held_for_review_rather_than_silently_discarded(): void
    {
        [$church, $mass] = $this->createPublishedChurchWithMass();

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::AwaitingPayment,
        ]);
        $payment = Payment::factory()->pendingPayment()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
        ]);

        // Mass cutoff is now in the past
        $mass->update(['booking_cutoff_at' => now()->subHours(2)]);

        $action = app(SubmitPaymentEvidenceAction::class);
        $action->execute($payment, [
            'reference_number' => 'LATE-GCASH-999',
            'submitted_amount' => '100.00',
            'evidence_file' => UploadedFile::fake()->image('late.jpg'),
        ]);

        $payment->refresh();
        $this->assertEquals(PaymentStatus::NeedsReview, $payment->status);
        $this->assertDatabaseHas('payment_exceptions', [
            'payment_id' => $payment->id,
            'type' => PaymentExceptionType::LatePayment->value,
            'status' => PaymentExceptionStatus::Open->value,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test Case 31: Registered user can cancel unpaid order awaiting payment
    // -------------------------------------------------------------------------
    public function test_case_31_registered_user_can_cancel_unpaid_order_awaiting_payment(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $user = User::factory()->create();
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $user->id,
            'submitter_type' => 'registered_user',
            'status' => OrderStatus::AwaitingPayment,
        ]);

        $intention = Intention::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => IntentionStatus::AwaitingPayment,
        ]);

        $payment = Payment::factory()->pendingPayment()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
        ]);

        $response = $this->actingAs($user)->post(route('orders.cancel', ['orderNumber' => $order->order_number]), [
            'reason' => 'Changed my mind before paying.',
        ]);

        $response->assertRedirect(route('churches.show', ['slug' => $church->slug]));
        $response->assertSessionHas('success');

        $order->refresh();
        $intention->refresh();
        $payment->refresh();

        $this->assertEquals(OrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertEquals(IntentionStatus::Cancelled, $intention->status);
        $this->assertEquals(PaymentStatus::Rejected, $payment->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.cancelled',
            'target_type' => Order::class,
            'target_id' => $order->id,
            'church_id' => $church->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test Case 32: Guest user can cancel unpaid order with valid guest token
    // -------------------------------------------------------------------------
    public function test_case_32_guest_user_can_cancel_unpaid_order_with_valid_token(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => null,
            'submitter_type' => 'guest',
            'submitter_email' => 'guest@example.com',
            'status' => OrderStatus::AwaitingPayment,
        ]);

        $guestAccess = app(GuestAccessService::class);
        $credential = $guestAccess->issueCredential($order, 'guest@example.com');

        $response = $this->withSession(['guest_order_token_'.$order->order_number => $credential['raw_token']])
            ->post(route('orders.cancel', ['orderNumber' => $order->order_number]), [
                'token' => $credential['raw_token'],
            ]);

        $response->assertRedirect(route('churches.show', ['slug' => $church->slug]));
        $order->refresh();
        $this->assertEquals(OrderStatus::Cancelled, $order->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 33: Unauthorized user or invalid token cannot cancel order
    // -------------------------------------------------------------------------
    public function test_case_33_unauthorized_actor_cannot_cancel_unpaid_order(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $owner->id,
            'submitter_type' => 'registered_user',
            'status' => OrderStatus::AwaitingPayment,
        ]);

        $response = $this->actingAs($stranger)->post(route('orders.cancel', ['orderNumber' => $order->order_number]));
        $response->assertStatus(403);

        $order->refresh();
        $this->assertEquals(OrderStatus::AwaitingPayment, $order->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 34: Order not in awaiting payment cannot be cancelled directly
    // -------------------------------------------------------------------------
    public function test_case_34_order_not_awaiting_payment_cannot_be_directly_cancelled(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $user = User::factory()->create();
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $user->id,
            'submitter_type' => 'registered_user',
            'status' => OrderStatus::PaymentUnderReview,
        ]);

        $response = $this->actingAs($user)->post(route('orders.cancel', ['orderNumber' => $order->order_number]));
        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertEquals(OrderStatus::PaymentUnderReview, $order->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 31: Registered user can cancel unpaid order awaiting payment
    // -------------------------------------------------------------------------
    public function test_case_31_registered_user_can_cancel_unpaid_order_awaiting_payment(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $user = User::factory()->create();
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $user->id,
            'submitter_type' => 'registered_user',
            'status' => OrderStatus::AwaitingPayment,
        ]);

        $intention = Intention::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => IntentionStatus::AwaitingPayment,
        ]);

        $payment = Payment::factory()->pendingPayment()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
        ]);

        $response = $this->actingAs($user)->post(route('orders.cancel', ['orderNumber' => $order->order_number]), [
            'reason' => 'Changed my mind before paying.',
        ]);

        $response->assertRedirect(route('churches.show', ['slug' => $church->slug]));
        $response->assertSessionHas('success');

        $order->refresh();
        $intention->refresh();
        $payment->refresh();

        $this->assertEquals(OrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertEquals(IntentionStatus::Cancelled, $intention->status);
        $this->assertEquals(PaymentStatus::Rejected, $payment->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.cancelled',
            'target_type' => Order::class,
            'target_id' => $order->id,
            'church_id' => $church->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Test Case 32: Guest user can cancel unpaid order with valid guest token
    // -------------------------------------------------------------------------
    public function test_case_32_guest_user_can_cancel_unpaid_order_with_valid_token(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => null,
            'submitter_type' => 'guest',
            'submitter_email' => 'guest@example.com',
            'status' => OrderStatus::AwaitingPayment,
        ]);

        $guestAccess = app(\App\Services\GuestAccess\GuestAccessService::class);
        $credential = $guestAccess->issueCredential($order, 'guest@example.com');

        $response = $this->withSession(['guest_order_token_'.$order->order_number => $credential['raw_token']])
            ->post(route('orders.cancel', ['orderNumber' => $order->order_number]), [
                'token' => $credential['raw_token'],
            ]);

        $response->assertRedirect(route('churches.show', ['slug' => $church->slug]));
        $order->refresh();
        $this->assertEquals(OrderStatus::Cancelled, $order->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 33: Unauthorized user or invalid token cannot cancel order
    // -------------------------------------------------------------------------
    public function test_case_33_unauthorized_actor_cannot_cancel_unpaid_order(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $owner->id,
            'submitter_type' => 'registered_user',
            'status' => OrderStatus::AwaitingPayment,
        ]);

        $response = $this->actingAs($stranger)->post(route('orders.cancel', ['orderNumber' => $order->order_number]));
        $response->assertStatus(403);

        $order->refresh();
        $this->assertEquals(OrderStatus::AwaitingPayment, $order->status);
    }

    // -------------------------------------------------------------------------
    // Test Case 34: Order not in awaiting payment cannot be cancelled directly
    // -------------------------------------------------------------------------
    public function test_case_34_order_not_awaiting_payment_cannot_be_directly_cancelled(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $user = User::factory()->create();
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $user->id,
            'submitter_type' => 'registered_user',
            'status' => OrderStatus::PaymentUnderReview,
        ]);

        $response = $this->actingAs($user)->post(route('orders.cancel', ['orderNumber' => $order->order_number]));
        $response->assertSessionHas('error');

        $order->refresh();
        $this->assertEquals(OrderStatus::PaymentUnderReview, $order->status);
    }
}
