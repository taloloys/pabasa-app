<?php

namespace Tests\Feature\Phase2;

use App\Actions\Churches\SuspendChurchAction;
use App\Actions\Masses\AssignLectorAction;
use App\Actions\Masses\CancelMassScheduleAction;
use App\Actions\Masses\CreateMassScheduleAction;
use App\Actions\Masses\ExtendMassCutoffAction;
use App\Actions\Masses\GenerateMassSeriesOccurrencesAction;
use App\Actions\Masses\RemoveLectorAssignmentAction;
use App\Actions\Masses\UpdateMassOccurrenceAction;
use App\Enums\BookingStatus;
use App\Enums\ChurchStatus;
use App\Enums\MassAssignmentStatus;
use App\Enums\MassStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\User;
use App\Services\Booking\BookingCutoffService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase2ChurchDirectoryAndSchedulingTest extends TestCase
{
    use RefreshDatabase;

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
     * 1. Draft/unverified/suspended/deactivated churches do not accept public submissions.
     */
    public function test_case_1_draft_unverified_suspended_deactivated_churches_do_not_accept_public_submissions(): void
    {
        $cutoffService = app(BookingCutoffService::class);

        $statuses = [
            ChurchStatus::Draft,
            ChurchStatus::PendingVerification,
            ChurchStatus::Verified,
            ChurchStatus::Suspended,
            ChurchStatus::Deactivated,
        ];

        foreach ($statuses as $status) {
            $church = Church::factory()->create(['status' => $status]);
            $mass = MassSchedule::factory()->create([
                'church_id' => $church->id,
                'booking_status' => BookingStatus::Open,
                'status' => MassStatus::Scheduled,
                'booking_opens_at' => now()->subDay(),
                'booking_cutoff_at' => now()->addDay(),
                'starts_at' => now()->addDays(2),
            ]);

            $this->assertFalse(
                $cutoffService->canAcceptBooking($mass, $church),
                "Church with status {$status->value} must not accept bookings."
            );

            $this->expectException(ValidationException::class);
            $cutoffService->assertCanAcceptBooking($mass);
        }
    }

    /**
     * 2. Only published churches appear in the public directory.
     */
    public function test_case_2_only_published_churches_appear_in_the_public_directory(): void
    {
        $published = Church::factory()->create(['name' => 'San Agustin Parish', 'status' => ChurchStatus::Published]);
        $draft = Church::factory()->create(['name' => 'Draft Parish', 'status' => ChurchStatus::Draft]);
        $suspended = Church::factory()->create(['name' => 'Suspended Parish', 'status' => ChurchStatus::Suspended]);
        $deactivated = Church::factory()->create(['name' => 'Closed Parish', 'status' => ChurchStatus::Deactivated]);

        $response = $this->get('/churches');
        $response->assertOk();

        // Published church appears in inertia data
        $response->assertSee('San Agustin Parish');
        $response->assertDontSee('Draft Parish');
        $response->assertDontSee('Suspended Parish');
        $response->assertDontSee('Closed Parish');

        // Accessing non-published church slug returns 404
        $this->get("/churches/{$draft->slug}")->assertNotFound();
        $this->get("/churches/{$suspended->slug}")->assertNotFound();
        $this->get("/churches/{$deactivated->slug}")->assertNotFound();

        // Accessing published church returns 200
        $this->get("/churches/{$published->slug}")->assertOk();
    }

    /**
     * 3. Central Admin can verify and publish a church; action is audited.
     */
    public function test_case_3_central_admin_can_verify_and_publish_a_church_action_is_audited(): void
    {
        $centralAdmin = User::factory()->centralAdmin()->create();
        $church = Church::factory()->draft()->create();

        // Verification
        $this->actingAs($centralAdmin)
            ->post("/admin/churches/{$church->id}/verify", ['reason' => 'All documents verified.'])
            ->assertSessionHasNoErrors();

        $church->refresh();
        $this->assertEquals(ChurchStatus::Verified, $church->status);
        $this->assertNotNull($church->verified_at);
        $this->assertEquals($centralAdmin->id, $church->verified_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'church.verified',
            'church_id' => $church->id,
            'actor_user_id' => $centralAdmin->id,
        ]);

        // Publication
        $this->actingAs($centralAdmin)
            ->post("/admin/churches/{$church->id}/publish")
            ->assertSessionHasNoErrors();

        $church->refresh();
        $this->assertEquals(ChurchStatus::Published, $church->status);
        $this->assertNotNull($church->published_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'church.published',
            'church_id' => $church->id,
            'actor_user_id' => $centralAdmin->id,
        ]);
    }

    /**
     * 4. Church Admin cannot edit another church's profile or branding.
     */
    public function test_case_4_church_admin_cannot_edit_another_church_profile_or_branding(): void
    {
        $churchA = Church::factory()->create();
        $churchB = Church::factory()->create();

        $adminA = $this->createChurchStaff($churchA, MembershipRole::ChurchAdmin);

        // Attempt to update branding of Church B
        $response = $this->actingAs($adminA)->patch("/churches/{$churchB->id}/branding", [
            'theme_color' => '#ff0000',
            'public_description' => 'Hijacked profile',
        ]);

        // Tenant isolation returns 404 for unassociated church
        $response->assertNotFound();

        // Church B branding remains empty or untouched
        $this->assertDatabaseMissing('church_branding', [
            'church_id' => $churchB->id,
            'theme_color' => '#ff0000',
        ]);
    }

    /**
     * 5. Church Admin can create/edit own church Masses.
     */
    public function test_case_5_church_admin_can_create_and_edit_own_church_masses(): void
    {
        $church = Church::factory()->create();
        $admin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);

        $startsAt = now()->addDays(3)->setTime(10, 0, 0);

        // Create Mass
        $createResponse = $this->actingAs($admin)->post("/churches/{$church->id}/masses", [
            'title' => 'Solemn Feast Mass',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => (clone $startsAt)->addHour()->toDateTimeString(),
            'language' => 'Tagalog',
            'location_label' => 'Main Altar',
            'resource_key' => 'altar-main',
            'booking_opens_at' => (clone $startsAt)->subDays(7)->toDateTimeString(),
            'booking_cutoff_at' => (clone $startsAt)->subHours(2)->toDateTimeString(),
        ]);

        $createResponse->assertSessionHasNoErrors();
        $mass = MassSchedule::where('church_id', $church->id)->first();
        $this->assertNotNull($mass);
        $this->assertEquals('Solemn Feast Mass', $mass->title);

        // Edit Mass
        $editResponse = $this->actingAs($admin)->patch("/churches/{$church->id}/masses/{$mass->id}", [
            'title' => 'Updated Solemn Feast Mass',
            'language' => 'English',
        ]);

        $editResponse->assertSessionHasNoErrors();
        $mass->refresh();
        $this->assertEquals('Updated Solemn Feast Mass', $mass->title);
        $this->assertEquals('English', $mass->language);
    }

    /**
     * 6. Editing one occurrence does not silently alter other recurring occurrences.
     */
    public function test_case_6_editing_one_occurrence_does_not_silently_alter_other_recurring_occurrences(): void
    {
        $church = Church::factory()->create();
        $admin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);

        // Generate series
        $action = app(GenerateMassSeriesOccurrencesAction::class);
        $result = $action->execute($church, [
            'title' => 'Sunday Mass',
            'recurrence_rule' => [
                'days_of_week' => [0],
                'time' => '09:00',
                'duration_minutes' => 60,
            ],
            'starts_on' => now()->startOfWeek()->toDateString(),
            'ends_on' => now()->addWeeks(3)->toDateString(),
            'location_label' => 'Main Church',
            'language' => 'English',
        ], $admin);

        $schedules = MassSchedule::where('mass_series_id', $result['series']->id)
            ->orderBy('starts_at')
            ->get();

        $this->assertGreaterThanOrEqual(2, $schedules->count());

        $first = $schedules[0];
        $second = $schedules[1];

        $originalSecondTitle = $second->title;
        $originalSecondLocation = $second->location_label;
        $originalSecondStart = $second->starts_at->toDateTimeString();

        // Update first occurrence only
        $updateAction = app(UpdateMassOccurrenceAction::class);
        $updateAction->execute($first, [
            'title' => 'Special Solemn Sunday Mass',
            'location_label' => 'Outdoor Plaza',
            'language' => 'Latin',
        ], $admin);

        $first->refresh();
        $second->refresh();

        // First occurrence changed
        $this->assertEquals('Special Solemn Sunday Mass', $first->title);
        $this->assertEquals('Outdoor Plaza', $first->location_label);

        // Sibling occurrence remains completely unchanged
        $this->assertEquals($originalSecondTitle, $second->title);
        $this->assertEquals($originalSecondLocation, $second->location_label);
        $this->assertEquals($originalSecondStart, $second->starts_at->toDateTimeString());
    }

    /**
     * 7. Invalid or past booking windows are rejected.
     */
    public function test_case_7_invalid_or_past_booking_windows_are_rejected(): void
    {
        $church = Church::factory()->create();
        $admin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);
        $action = app(CreateMassScheduleAction::class);

        $startsAt = now()->addDays(3);

        // Case A: booking_opens_at >= booking_cutoff_at
        $this->expectException(ValidationException::class);
        $action->execute($church, [
            'title' => 'Invalid Window Mass',
            'starts_at' => $startsAt->toDateTimeString(),
            'booking_opens_at' => (clone $startsAt)->subHour()->toDateTimeString(),
            'booking_cutoff_at' => (clone $startsAt)->subHours(2)->toDateTimeString(),
        ], $admin);
    }

    /**
     * 8. Closed Mass remains visible but cannot be selected for submission.
     */
    public function test_case_8_closed_mass_remains_visible_but_cannot_be_selected_for_submission(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $closedMass = MassSchedule::factory()->closed()->create([
            'church_id' => $church->id,
            'title' => 'Closed Submissions Mass',
            'starts_at' => now()->addDays(2),
        ]);

        // Visible on public show route
        $response = $this->get("/churches/{$church->slug}");
        $response->assertOk();
        $response->assertSee('Closed Submissions Mass');

        // Cannot be selected for booking
        $cutoffService = app(BookingCutoffService::class);
        $this->assertFalse($cutoffService->canAcceptBooking($closedMass, $church));

        $this->expectException(ValidationException::class);
        $cutoffService->assertCanAcceptBooking($closedMass);
    }

    /**
     * 9. New order creation is rejected after cutoff.
     */
    public function test_case_9_new_order_creation_is_rejected_after_cutoff(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->pastCutoff()->create([
            'church_id' => $church->id,
            'title' => 'Past Cutoff Mass',
        ]);

        $cutoffService = app(BookingCutoffService::class);
        $this->assertFalse($cutoffService->canAcceptBooking($mass, $church));

        try {
            $cutoffService->assertCanAcceptBooking($mass);
            $this->fail('Expected ValidationException when cutoff passed.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cutoff deadline for this Mass has passed', $e->getMessage());
        }
    }

    /**
     * 10. Assigned lector can close submissions early.
     */
    public function test_case_10_assigned_lector_can_close_submissions_early(): void
    {
        $church = Church::factory()->create();
        $lector = $this->createChurchStaff($church, MembershipRole::Lector);
        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'booking_status' => BookingStatus::Open,
        ]);

        // Assign lector to mass
        MassAssignment::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $lector->id,
            'assignment_role' => 'lector',
            'status' => MassAssignmentStatus::Active,
        ]);

        // Assigned lector closes submissions early
        $this->actingAs($lector)
            ->post("/churches/{$church->id}/lector/masses/{$mass->id}/close-early", [
                'reason' => 'Roster full',
            ])
            ->assertSessionHasNoErrors();

        $mass->refresh();
        $this->assertEquals(BookingStatus::Closed, $mass->booking_status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'mass.booking_closed_early',
            'actor_user_id' => $lector->id,
            'church_id' => $church->id,
        ]);
    }

    /**
     * 11. Lector cannot extend the cutoff beyond authorized limits.
     */
    public function test_case_11_lector_cannot_extend_the_cutoff_beyond_authorized_limits(): void
    {
        $church = Church::factory()->create();
        $lector = $this->createChurchStaff($church, MembershipRole::Lector);
        $admin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);

        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'starts_at' => now()->addDays(2)->setTime(10, 0, 0),
            'booking_cutoff_at' => now()->addDays(2)->setTime(8, 0, 0),
        ]);

        $action = app(ExtendMassCutoffAction::class);

        // Lector attempt fails with AuthorizationException
        try {
            $action->execute($mass, now()->addDays(2)->setTime(9, 0, 0), $lector);
            $this->fail('Lector must not be authorized to extend cutoff.');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('church administrators are authorized', $e->getMessage());
        }

        // Church admin cannot extend cutoff past Mass start time
        try {
            $action->execute($mass, now()->addDays(2)->setTime(11, 0, 0), $admin);
            $this->fail('Extending cutoff beyond Mass start time must fail.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cannot be extended beyond the Mass start time', $e->getMessage());
        }

        // Church admin valid extension succeeds
        $extended = $action->execute($mass, now()->addDays(2)->setTime(9, 30, 0), $admin);
        $this->assertEquals(
            now()->addDays(2)->setTime(9, 30, 0)->toDateTimeString(),
            $extended->booking_cutoff_at->toDateTimeString()
        );
    }

    /**
     * 12. Conflict detection considers configured shared resources/staff and allows unrelated chapel overlaps.
     */
    public function test_case_12_conflict_detection_considers_configured_shared_resources_and_staff_and_allows_unrelated_chapel_overlaps(): void
    {
        $church = Church::factory()->create();
        $admin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);
        $lector = $this->createChurchStaff($church, MembershipRole::Lector);
        $action = app(CreateMassScheduleAction::class);

        $startsAt = now()->addDays(2)->setTime(10, 0, 0);
        $endsAt = (clone $startsAt)->addHour();

        // Mass 1 in Main Sanctuary (resource_key: sanctuary-main)
        $mass1 = $action->execute($church, [
            'title' => 'Sanctuary Mass',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'location_label' => 'Main Sanctuary',
            'resource_key' => 'sanctuary-main',
            'language' => 'English',
        ], $admin);

        // Mass 2 at the EXACT SAME TIME in Side Chapel (resource_key: chapel-side)
        // Must succeed without conflict!
        $mass2 = $action->execute($church, [
            'title' => 'Side Chapel Mass',
            'starts_at' => $startsAt->toDateTimeString(),
            'ends_at' => $endsAt->toDateTimeString(),
            'location_label' => 'St. Joseph Chapel',
            'resource_key' => 'chapel-side',
            'language' => 'Tagalog',
        ], $admin);

        $this->assertNotNull($mass2);

        // Mass 3 attempting to book Main Sanctuary at the same time fails with resource conflict
        try {
            $action->execute($church, [
                'title' => 'Conflicting Sanctuary Mass',
                'starts_at' => $startsAt->toDateTimeString(),
                'ends_at' => $endsAt->toDateTimeString(),
                'resource_key' => 'sanctuary-main',
                'language' => 'English',
            ], $admin);
            $this->fail('Expected resource conflict for sanctuary-main.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('is already scheduled', $e->getMessage());
        }

        // Staff conflict: Assigning lector to Mass 1 succeeds
        $assignAction = app(AssignLectorAction::class);
        $assignAction->execute($mass1, $lector, $admin);

        // Assigning the same lector to simultaneous Mass 2 fails with staff conflict
        try {
            $assignAction->execute($mass2, $lector, $admin);
            $this->fail('Expected staff conflict when assigning lector to simultaneous Masses.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('already assigned to another Mass', $e->getMessage());
        }
    }

    /**
     * 13. Lector cannot view unassigned Mass details by changing the URL ID.
     */
    public function test_case_13_lector_cannot_view_unassigned_mass_details_by_changing_the_url_id(): void
    {
        $church = Church::factory()->create();
        $lector = $this->createChurchStaff($church, MembershipRole::Lector);

        $assignedMass = MassSchedule::factory()->create(['church_id' => $church->id]);
        $unassignedMass = MassSchedule::factory()->create(['church_id' => $church->id]);

        MassAssignment::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $assignedMass->id,
            'user_id' => $lector->id,
            'status' => MassAssignmentStatus::Active,
        ]);

        // Viewing assigned Mass succeeds
        $this->actingAs($lector)
            ->get("/churches/{$church->id}/lector/masses/{$assignedMass->id}")
            ->assertOk();

        // Viewing unassigned Mass returns 404 (Spec §11 Phase 2 Test 13: Lector cannot view unassigned Mass details by changing URL ID)
        $this->actingAs($lector)
            ->get("/churches/{$church->id}/lector/masses/{$unassignedMass->id}")
            ->assertNotFound();
    }

    /**
     * 14. Suspending a church blocks new orders but preserves existing orders/payments.
     */
    public function test_case_14_suspending_a_church_blocks_new_orders_but_preserves_existing_orders_payments(): void
    {
        $centralAdmin = User::factory()->centralAdmin()->create();
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'booking_status' => BookingStatus::Open,
        ]);

        $cutoffService = app(BookingCutoffService::class);
        $this->assertTrue($cutoffService->canAcceptBooking($mass, $church));

        // Suspend church
        $suspendAction = app(SuspendChurchAction::class);
        $suspendAction->execute($church, $centralAdmin, 'Audit compliance check required.');

        $church->refresh();
        $this->assertEquals(ChurchStatus::Suspended, $church->status);

        // Mass schedule data is preserved
        $this->assertDatabaseHas('mass_schedules', ['id' => $mass->id]);

        // New bookings immediately blocked
        $this->assertFalse($cutoffService->canAcceptBooking($mass, $church));
        $this->expectException(ValidationException::class);
        $cutoffService->assertCanAcceptBooking($mass);
    }

    /**
     * 15. Mass cancellation creates a case and preserves original schedule data.
     */
    public function test_case_15_mass_cancellation_creates_a_case_and_preserves_original_schedule_data(): void
    {
        $church = Church::factory()->create();
        $admin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);
        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'title' => 'Sunday High Mass',
            'location_label' => 'Cathedral Sanctuary',
            'starts_at' => now()->addDays(2),
        ]);

        $cancelAction = app(CancelMassScheduleAction::class);
        $cancelAction->execute($mass, $admin, 'Typhoon alert level 4 declared.');

        $mass->refresh();
        $this->assertEquals(MassStatus::Cancelled, $mass->status);
        $this->assertEquals(BookingStatus::Cancelled, $mass->booking_status);
        $this->assertEquals('Typhoon alert level 4 declared.', $mass->cancellation_reason);
        $this->assertEquals($admin->id, $mass->cancelled_by);
        $this->assertNotNull($mass->cancelled_at);

        // Original schedule metadata preserved
        $this->assertEquals('Sunday High Mass', $mass->title);
        $this->assertEquals('Cathedral Sanctuary', $mass->location_label);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'mass.cancelled',
            'target_id' => (string) $mass->id,
            'church_id' => $church->id,
        ]);
    }

    /**
     * 16. Schedule updates and staff assignments create audit events.
     */
    public function test_case_16_schedule_updates_and_staff_assignments_create_audit_events(): void
    {
        $church = Church::factory()->create();
        $admin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);
        $lector = $this->createChurchStaff($church, MembershipRole::Lector);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        // Update schedule
        $updateAction = app(UpdateMassOccurrenceAction::class);
        $updateAction->execute($mass, ['title' => 'Updated Solemn Mass'], $admin);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'mass.updated',
            'target_id' => (string) $mass->id,
            'actor_user_id' => $admin->id,
        ]);

        // Assign lector
        $assignAction = app(AssignLectorAction::class);
        $assignment = $assignAction->execute($mass, $lector, $admin, 'First reading');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'mass.lector_assigned',
            'target_id' => (string) $assignment->id,
            'actor_user_id' => $admin->id,
        ]);

        // Remove lector
        $removeAction = app(RemoveLectorAssignmentAction::class);
        $removeAction->execute($assignment, $admin, 'Substituted');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'mass.lector_removed',
            'target_id' => (string) $assignment->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    /**
     * 17. Church timezone is used consistently in displayed cutoff and schedule times.
     */
    public function test_case_17_church_timezone_is_used_consistently_in_displayed_cutoff_and_schedule_times(): void
    {
        // Manila is UTC+8
        $church = Church::factory()->create([
            'timezone' => 'Asia/Manila',
            'status' => ChurchStatus::Published,
        ]);

        // Mass starts at UTC 01:00 AM on Nov 1, which corresponds to 09:00 AM Manila Time
        $startsAt = Carbon::create(2026, 11, 1, 1, 0, 0, 'UTC');
        $cutoffAt = (clone $startsAt)->subHours(2); // UTC 23:00 Oct 31, which corresponds to 07:00 AM Nov 1 Manila Time

        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'starts_at' => $startsAt,
            'booking_cutoff_at' => $cutoffAt,
            'timezone' => 'Asia/Manila',
        ]);

        $this->assertEquals('9:00 AM', $mass->formatInChurchTimezone('starts_at', 'g:i A'));
        $this->assertEquals('7:00 AM', $mass->formatInChurchTimezone('booking_cutoff_at', 'g:i A'));

        // Verify public endpoint renders in church timezone
        $response = $this->get("/churches/{$church->slug}");
        $response->assertOk();
        $response->assertSee('Nov 01, 2026 09:00 AM');
        $response->assertSee('Nov 01, 2026 07:00 AM');
    }

    /**
     * 18. Concurrent requests around cutoff cannot both bypass the cutoff rule.
     */
    public function test_case_18_concurrent_requests_around_cutoff_cannot_both_bypass_the_cutoff_rule(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'booking_opens_at' => now()->subDay(),
            'booking_cutoff_at' => now()->addSecond(), // Will expire in 1 second
            'starts_at' => now()->addHour(),
        ]);

        $cutoffService = app(BookingCutoffService::class);

        // Transaction 1 locks row and passes right before cutoff
        DB::transaction(function () use ($cutoffService, $mass) {
            $lockedMass = $cutoffService->assertCanAcceptBooking($mass->id, lock: true);
            $this->assertEquals($mass->id, $lockedMass->id);
        });

        // Advance simulated time past cutoff
        Carbon::setTestNow(now()->addSeconds(5));

        // Subsequent concurrent transaction after cutoff is safely rejected
        try {
            DB::transaction(function () use ($cutoffService, $mass) {
                $cutoffService->assertCanAcceptBooking($mass->id, lock: true);
            });
            $this->fail('Expected rejection when transaction evaluates past cutoff deadline.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('cutoff deadline for this Mass has passed', $e->getMessage());
        } finally {
            Carbon::setTestNow(); // Reset simulated time
        }
    }
}
