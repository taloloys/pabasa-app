<?php

namespace Tests\Feature\Phase4;

use App\Actions\Payments\VerifyPaymentAction;
use App\Actions\ReadingLists\FinalizeDueReadingListsAction;
use App\Actions\ReadingLists\FinalizeReadingListAction;
use App\Actions\ReadingLists\ReportReadingListEntryIssueAction;
use App\Enums\IntentionStatus;
use App\Enums\MassAssignmentStatus;
use App\Enums\MembershipStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListEntryIssueStatus;
use App\Enums\ReadingListEntryStatus;
use App\Enums\ReadingListIssueResolution;
use App\Enums\ReadingListStatus;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\Intention;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\ReadingListVersion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class ReadingListWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_lector_can_reorder_entries_and_foreign_or_duplicate_ids_are_rejected(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $first = $this->addConfirmedEntry($church, $mass, $readingList);
        $second = $this->addConfirmedEntry($church, $mass, $readingList);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.reorder', [$church, $mass]), [
                'entry_ids' => [$second->id, $first->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$second->id, $first->id],
            $readingList->entries()->pluck('id')->all(),
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reading_list.reordered',
            'actor_user_id' => $lector->id,
            'church_id' => $church->id,
        ]);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.reorder', [$church, $mass]), [
                'entry_ids' => [$first->id, $first->id],
            ])
            ->assertSessionHasErrors('entry_ids');

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.reorder', [$church, $mass]), [
                'entry_ids' => [$first->id],
            ])
            ->assertSessionHasErrors('entry_ids');

        $foreignEntry = ReadingListEntry::factory()->create();
        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.reorder', [$church, $mass]), [
                'entry_ids' => [$first->id, $foreignEntry->id],
            ])
            ->assertSessionHasErrors('entry_ids');
    }

    public function test_finalization_creates_immutable_version_and_freezes_reordering(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $entry = $this->addConfirmedEntry($church, $mass, $readingList);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.finalize', [$church, $mass]))
            ->assertSessionHasNoErrors();

        $readingList->refresh();
        $version = ReadingListVersion::query()->where('reading_list_id', $readingList->id)->firstOrFail();

        $this->assertSame(ReadingListStatus::Finalized, $readingList->status);
        $this->assertSame(1, $readingList->version);
        $this->assertSame($entry->intention->wording, $version->snapshot['entries'][0]['wording']);
        $this->assertSame($lector->id, $version->created_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reading_list.finalized',
            'actor_user_id' => $lector->id,
            'church_id' => $church->id,
        ]);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.reorder', [$church, $mass]), [
                'entry_ids' => [$entry->id],
            ])
            ->assertSessionHasErrors('reading_list');

        $this->assertDatabaseCount('reading_list_versions', 1);
    }

    public function test_scheduled_finalization_is_idempotent_after_the_configured_deadline(): void
    {
        [$church, $mass, , $readingList] = $this->assignedReadingList();
        $this->addConfirmedEntry($church, $mass, $readingList);
        $mass->update([
            'starts_at' => now()->addMinutes(30),
            'ends_at' => now()->addMinutes(90),
            'finalization_deadline' => now()->subMinute(),
        ]);

        $scheduledAction = app(FinalizeDueReadingListsAction::class);
        $this->assertSame(1, $scheduledAction->execute());
        $this->assertSame(0, $scheduledAction->execute());

        app(FinalizeReadingListAction::class)->executeScheduled($readingList);

        $this->assertSame(ReadingListStatus::Finalized, $readingList->refresh()->status);
        $this->assertSame(1, ReadingListVersion::query()->where('reading_list_id', $readingList->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reading_list.finalized',
            'target_id' => (string) $readingList->id,
            'actor_type' => 'system',
            'actor_user_id' => null,
        ]);
        $this->assertSame(
            1,
            DB::table('audit_logs')
                ->where('action', 'reading_list.finalized')
                ->where('target_id', (string) $readingList->id)
                ->count(),
        );
    }

    public function test_reading_list_version_snapshots_cannot_be_updated_or_deleted(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $this->addConfirmedEntry($church, $mass, $readingList);
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);
        $version = $readingList->versions()->firstOrFail();

        try {
            $version->update(['reason' => 'Attempted overwrite']);
            $this->fail('A reading-list snapshot must not be updated.');
        } catch (LogicException) {
            $this->assertDatabaseHas('reading_list_versions', [
                'id' => $version->id,
                'reason' => null,
            ]);
        }

        try {
            $version->delete();
            $this->fail('A reading-list snapshot must not be deleted.');
        } catch (LogicException) {
            $this->assertDatabaseHas('reading_list_versions', [
                'id' => $version->id,
                'reading_list_id' => $readingList->id,
            ]);
        }
    }

    public function test_reopening_requires_a_reason_adds_late_confirmed_intentions_and_preserves_versions(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $this->addConfirmedEntry($church, $mass, $readingList);
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);

        $lateIntention = $this->confirmedIntention($church, $mass);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.reopen', [$church, $mass]), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.reopen', [$church, $mass]), [
                'reason' => 'Central Admin approved a late payment exception.',
            ])
            ->assertSessionHasNoErrors();

        $readingList->refresh();
        $this->assertSame(ReadingListStatus::Reopened, $readingList->status);
        $this->assertDatabaseHas('reading_list_entries', [
            'reading_list_id' => $readingList->id,
            'intention_id' => $lateIntention->id,
        ]);
        $this->assertSame(1, ReadingListVersion::query()->where('reading_list_id', $readingList->id)->count());

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.finalize', [$church, $mass]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, ReadingListVersion::query()->where('reading_list_id', $readingList->id)->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reading_list.reopened',
            'reason' => 'Central Admin approved a late payment exception.',
            'actor_user_id' => $lector->id,
        ]);
    }

    public function test_completion_can_be_corrected_with_a_reason_before_the_list_is_completed(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $completedEntry = $this->addConfirmedEntry($church, $mass, $readingList);
        $pendingEntry = $this->addConfirmedEntry($church, $mass, $readingList);
        $centralAdmin = User::factory()->centralAdmin()->create();
        $this->assertFalse($centralAdmin->can('correctEntryCompletion', $readingList));
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.entries.complete', [$church, $mass, $completedEntry]))
            ->assertSessionHasNoErrors();

        $completedAt = $completedEntry->refresh()->completed_at;
        $this->assertSame(OrderStatus::Completed, $completedEntry->intention->order->refresh()->status);
        $this->assertSame(ReadingListStatus::Finalized, $readingList->refresh()->status);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.entries.correct-completion', [$church, $mass, $completedEntry]), [
                'reason' => ' ',
            ])
            ->assertSessionHasErrors('reason');

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.entries.correct-completion', [$church, $mass, $completedEntry]), [
                'reason' => 'The lector marked the wrong intention as read.',
            ])
            ->assertSessionHasNoErrors();

        $completedEntry->refresh();
        $this->assertSame(ReadingListEntryStatus::Pending, $completedEntry->status);
        $this->assertNull($completedEntry->completed_at);
        $this->assertNull($completedEntry->completed_by);
        $this->assertSame(IntentionStatus::Confirmed, $completedEntry->intention->refresh()->status);
        $this->assertSame(OrderStatus::Confirmed, $completedEntry->intention->order->refresh()->status);
        $this->assertSame(ReadingListEntryStatus::Pending, $pendingEntry->refresh()->status);
        $this->assertSame(ReadingListStatus::Finalized, $readingList->refresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reading_list.entry_completed',
            'target_id' => (string) $completedEntry->id,
            'actor_user_id' => $lector->id,
        ]);
        $correctionEvent = DB::table('audit_logs')
            ->where('action', 'reading_list.entry_completion_corrected')
            ->where('target_id', (string) $completedEntry->id)
            ->first();

        $this->assertNotNull($correctionEvent);
        $this->assertSame('The lector marked the wrong intention as read.', $correctionEvent->reason);
        $before = json_decode($correctionEvent->before_data, true);
        $this->assertSame(ReadingListEntryStatus::Completed->value, $before['entry_status']);
        $this->assertSame($lector->id, $before['completed_by']);
        $this->assertSame($completedAt->toISOString(), $before['completed_at']);
    }

    public function test_every_entry_must_be_completed_before_list_and_orders_complete(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $first = $this->addConfirmedEntry($church, $mass, $readingList);
        $second = $this->addConfirmedEntry($church, $mass, $readingList);
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.entries.complete', [$church, $mass, $first]))
            ->assertSessionHasNoErrors();

        $readingList->refresh();
        $this->assertSame(ReadingListStatus::Finalized, $readingList->status);
        $this->assertSame(ReadingListEntryStatus::Completed, $first->refresh()->status);
        $this->assertSame($lector->id, $first->completed_by);
        $this->assertNotNull($first->completed_at);
        $this->assertSame(IntentionStatus::Completed, $first->intention->refresh()->status);
        $this->assertSame(OrderStatus::Completed, $first->intention->order->refresh()->status);
        $this->assertSame(OrderStatus::Confirmed, $second->intention->order->refresh()->status);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.entries.complete', [$church, $mass, $second]))
            ->assertSessionHasNoErrors();

        $this->assertSame(ReadingListStatus::Completed, $readingList->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reading_list.completed',
            'actor_user_id' => $lector->id,
            'church_id' => $church->id,
        ]);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.entries.correct-completion', [$church, $mass, $first]), [
                'reason' => 'This list has already been completed.',
            ])
            ->assertSessionHasErrors('reading_list');
        $this->assertSame(ReadingListEntryStatus::Completed, $first->refresh()->status);
    }

    public function test_entry_cannot_be_completed_when_its_order_belongs_to_another_church_or_mass(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $entry = $this->addConfirmedEntry($church, $mass, $readingList);
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);

        $foreignChurch = Church::factory()->create();
        $foreignMass = MassSchedule::factory()->create(['church_id' => $foreignChurch->id]);
        $entry->intention->order->forceFill([
            'church_id' => $foreignChurch->id,
            'mass_schedule_id' => $foreignMass->id,
        ])->save();

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.entries.complete', [$church, $mass, $entry]))
            ->assertSessionHasErrors('entry');

        $this->assertSame(ReadingListEntryStatus::Pending, $entry->refresh()->status);
        $this->assertSame(IntentionStatus::Confirmed, $entry->intention->refresh()->status);
    }

    public function test_unassigned_lector_cannot_view_or_change_another_lectors_mass_list(): void
    {
        [$church, $mass, , $readingList] = $this->assignedReadingList();
        $outsider = User::factory()->create();
        ChurchMembership::factory()->lector()->create([
            'church_id' => $church->id,
            'user_id' => $outsider->id,
            'status' => MembershipStatus::Active,
        ]);

        $this->actingAs($outsider)
            ->get(route('lector.masses.show', [$church, $mass]))
            ->assertNotFound();

        $this->actingAs($outsider)
            ->post(route('lector.masses.reading-list.finalize', [$church, $mass]))
            ->assertNotFound();

        $this->assertSame(ReadingListStatus::Preparing, $readingList->refresh()->status);
    }

    public function test_assigned_lector_can_download_a_pdf_and_unassigned_lector_cannot(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $this->addConfirmedEntry($church, $mass, $readingList);
        $outsider = User::factory()->create();
        ChurchMembership::factory()->lector()->create([
            'church_id' => $church->id,
            'user_id' => $outsider->id,
            'status' => MembershipStatus::Active,
        ]);

        $response = $this->actingAs($lector)
            ->get(route('lector.masses.reading-list.pdf', [$church, $mass]))
            ->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));

        app(FinalizeReadingListAction::class)->execute($readingList, $lector);
        $this->actingAs($lector)
            ->get(route('lector.masses.reading-list.pdf', [$church, $mass]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($outsider)
            ->get(route('lector.masses.reading-list.pdf', [$church, $mass]))
            ->assertNotFound();
    }

    public function test_finalized_pdf_uses_its_immutable_snapshot(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $entry = $this->addConfirmedEntry($church, $mass, $readingList);
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);
        $snapshotEntry = $readingList->versions()->firstOrFail()->snapshot['entries'][0];

        DB::table('intentions')->where('id', $entry->intention_id)->update([
            'person_or_family_name' => 'Changed after finalization',
            'wording' => 'Changed after finalization',
        ]);

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->once()->andReturnSelf();
        $pdf->shouldReceive('download')
            ->once()
            ->with("reading-list-mass-{$mass->id}-v{$readingList->version}.pdf")
            ->andReturn(response('', 200));

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('lector.reading-list-pdf', \Mockery::on(function (array $data) use ($snapshotEntry) {
                $this->assertSame([[
                    'id' => $snapshotEntry['id'],
                    'person_or_family_name' => $snapshotEntry['person_or_family_name'],
                    'pronunciation_hint' => $snapshotEntry['pronunciation_hint'] ?? null,
                    'wording' => $snapshotEntry['wording'],
                    'category_label' => $snapshotEntry['category_label'],
                ]], $data['entries']);

                return true;
            }))
            ->andReturn($pdf);

        $this->actingAs($lector)
            ->get(route('lector.masses.reading-list.pdf', [$church, $mass]))
            ->assertOk();
    }

    public function test_revoked_lector_membership_denies_reading_list_access_even_if_assignment_remains_active(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $lector->churchMemberships()->where('church_id', $church->id)->update([
            'status' => MembershipStatus::Revoked->value,
        ]);

        $this->assertFalse($lector->can('reorder', $readingList));

        $this->actingAs($lector)
            ->get(route('lector.masses.show', [$church, $mass]))
            ->assertNotFound();
    }

    public function test_payment_verified_after_finalization_stays_visible_but_outside_the_frozen_list(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $this->addConfirmedEntry($church, $mass, $readingList);
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);

        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::AwaitingPayment,
        ]);
        $intention = Intention::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => IntentionStatus::AwaitingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'status' => PaymentStatus::PendingVerification,
        ]);

        app(VerifyPaymentAction::class)->execute($payment, User::factory()->centralAdmin()->create());

        $this->assertSame(1, $readingList->entries()->count());
        $this->assertDatabaseMissing('reading_list_entries', [
            'reading_list_id' => $readingList->id,
            'intention_id' => $intention->id,
        ]);

        $this->actingAs($lector)
            ->get(route('lector.masses.show', [$church, $mass]))
            ->assertInertia(fn ($page) => $page
                ->where('readingList.status', ReadingListStatus::Finalized->value)
                ->where('pendingIntentions.0.id', $intention->id));
    }

    public function test_only_payment_verified_intentions_are_added_to_a_preparing_list(): void
    {
        [$church, $mass, , $readingList] = $this->assignedReadingList();
        $payingOrder = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::AwaitingPayment,
        ]);
        $verifiedIntention = Intention::factory()->create([
            'order_id' => $payingOrder->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => IntentionStatus::AwaitingPayment,
        ]);
        $unpaidOrder = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => OrderStatus::AwaitingPayment,
        ]);
        $unpaidIntention = Intention::factory()->create([
            'order_id' => $unpaidOrder->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => IntentionStatus::AwaitingPayment,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $payingOrder->id,
            'church_id' => $church->id,
            'status' => PaymentStatus::PendingVerification,
        ]);

        $this->assertSame(0, $readingList->entries()->count());

        app(VerifyPaymentAction::class)->execute($payment, User::factory()->centralAdmin()->create());

        $this->assertDatabaseHas('reading_list_entries', [
            'reading_list_id' => $readingList->id,
            'intention_id' => $verifiedIntention->id,
        ]);
        $this->assertDatabaseMissing('reading_list_entries', [
            'reading_list_id' => $readingList->id,
            'intention_id' => $unpaidIntention->id,
        ]);
    }

    public function test_lector_workbench_never_shows_an_unverified_intention_even_if_its_status_is_stale(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $order = Order::factory()->confirmed()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);
        Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'status' => PaymentStatus::PendingVerification,
        ]);
        $intention = Intention::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => IntentionStatus::Confirmed,
        ]);
        ReadingListEntry::factory()->create([
            'reading_list_id' => $readingList->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'intention_id' => $intention->id,
        ]);

        $this->actingAs($lector)
            ->get(route('lector.masses.show', [$church, $mass]))
            ->assertInertia(fn ($page) => $page
                ->where('readingList.entries', [])
                ->where('pendingIntentions', []));
    }

    public function test_lector_can_report_private_issue_and_owner_sees_only_generic_review_status(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $entry = $this->addConfirmedEntry($church, $mass, $readingList);
        $owner = User::factory()->create();
        $entry->intention->order->update(['user_id' => $owner->id]);

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.entries.report-issue', [$church, $mass, $entry]), [
                'private_note' => 'Check the pronunciation with parish staff.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reading_list_entries', [
            'id' => $entry->id,
            'status' => ReadingListEntryStatus::IssueReported->value,
        ]);
        $this->assertDatabaseHas('reading_list_entry_issues', [
            'reading_list_entry_id' => $entry->id,
            'status' => ReadingListEntryIssueStatus::Open->value,
            'private_note' => 'Check the pronunciation with parish staff.',
        ]);

        $this->actingAs($owner)
            ->get(route('orders.tracking'))
            ->assertInertia(fn ($page) => $page
                ->where('orders.0.intentions.0.reading_issue_under_review', true)
                ->missing('orders.0.intentions.0.readingListEntry'))
            ->assertDontSee('Check the pronunciation with parish staff.');
    }

    public function test_open_issue_blocks_finalization_and_pdf_download(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $entry = $this->addConfirmedEntry($church, $mass, $readingList);

        app(ReportReadingListEntryIssueAction::class)
            ->execute($readingList, $entry, $lector, 'Please review this entry.');

        $this->actingAs($lector)
            ->post(route('lector.masses.reading-list.finalize', [$church, $mass]))
            ->assertSessionHasErrors('reading_list');

        $this->actingAs($lector)
            ->get(route('lector.masses.reading-list.pdf', [$church, $mass]))
            ->assertStatus(409);

        $this->assertDatabaseCount('reading_list_versions', 0);
    }

    public function test_church_admin_can_resolve_issue_as_excluded_without_changing_payment_status(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $entry = $this->addConfirmedEntry($church, $mass, $readingList);
        $retainedEntry = $this->addConfirmedEntry($church, $mass, $readingList);
        $issue = app(ReportReadingListEntryIssueAction::class)
            ->execute($readingList, $entry, $lector, 'Name needs staff review.');
        $churchAdmin = User::factory()->create();
        ChurchMembership::factory()->churchAdmin()->create([
            'church_id' => $church->id,
            'user_id' => $churchAdmin->id,
        ]);
        $otherChurch = Church::factory()->create();
        $otherChurchAdmin = User::factory()->create();
        ChurchMembership::factory()->churchAdmin()->create([
            'church_id' => $otherChurch->id,
            'user_id' => $otherChurchAdmin->id,
        ]);

        $this->actingAs($otherChurchAdmin)
            ->post(route('church.masses.reading-list.entries.issues.resolve', [
                $church,
                $mass,
                $entry,
                $issue,
            ]), [
                'resolution' => ReadingListIssueResolution::Exclude->value,
            ])
            ->assertNotFound();

        $this->actingAs($churchAdmin)
            ->post(route('church.masses.reading-list.entries.issues.resolve', [
                $church,
                $mass,
                $entry,
                $issue,
            ]), [
                'resolution' => ReadingListIssueResolution::Exclude->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reading_list_entries', [
            'id' => $entry->id,
            'status' => ReadingListEntryStatus::Excluded->value,
        ]);
        $this->assertDatabaseHas('reading_list_entry_issues', [
            'id' => $issue->id,
            'status' => ReadingListEntryIssueStatus::Resolved->value,
            'resolution' => ReadingListIssueResolution::Exclude->value,
            'resolved_by' => $churchAdmin->id,
        ]);
        $this->assertSame(PaymentStatus::Verified, $entry->intention->order->payments()->firstOrFail()->status);

        app(FinalizeReadingListAction::class)->execute($readingList, $lector);
        $snapshot = $readingList->versions()->firstOrFail()->snapshot;
        $this->assertSame([$retainedEntry->id], array_column($snapshot['entries'], 'id'));
    }

    public function test_church_admin_hint_is_separate_and_frozen_into_the_finalized_pdf_version(): void
    {
        [$church, $mass, $lector, $readingList] = $this->assignedReadingList();
        $entry = $this->addConfirmedEntry($church, $mass, $readingList);
        $churchAdmin = User::factory()->create();
        ChurchMembership::factory()->churchAdmin()->create([
            'church_id' => $church->id,
            'user_id' => $churchAdmin->id,
        ]);

        $this->actingAs($lector)
            ->patch(route('church.masses.reading-list.entries.pronunciation', [
                $church,
                $mass,
                $entry,
            ]), [
                'pronunciation_hint' => 'ah-MAH-deh-us',
            ])
            ->assertForbidden();

        $this->actingAs($churchAdmin)
            ->patch(route('church.masses.reading-list.entries.pronunciation', [
                $church,
                $mass,
                $entry,
            ]), [
                'pronunciation_hint' => 'ah-MAH-deh-us',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('ah-MAH-deh-us', $entry->intention->refresh()->pronunciation_hint);
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);
        $snapshotEntry = $readingList->versions()->firstOrFail()->snapshot['entries'][0];
        $this->assertSame('ah-MAH-deh-us', $snapshotEntry['pronunciation_hint']);

        $this->actingAs($lector)
            ->get(route('lector.masses.show', [$church, $mass]))
            ->assertInertia(fn ($page) => $page->where('readingList.entries.0.pronunciationHint', 'ah-MAH-deh-us'));

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('setOptions')->once()->andReturnSelf();
        $pdf->shouldReceive('download')
            ->once()
            ->with("reading-list-mass-{$mass->id}-v{$readingList->version}.pdf")
            ->andReturn(response('', 200));

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('lector.reading-list-pdf', \Mockery::on(function (array $data) {
                $this->assertSame('ah-MAH-deh-us', $data['entries'][0]['pronunciation_hint']);

                return true;
            }))
            ->andReturn($pdf);

        $this->actingAs($lector)
            ->get(route('lector.masses.reading-list.pdf', [$church, $mass]))
            ->assertOk();
    }

    private function assignedReadingList(): array
    {
        $church = Church::factory()->create();
        $lector = User::factory()->create();
        ChurchMembership::factory()->lector()->create([
            'church_id' => $church->id,
            'user_id' => $lector->id,
            'status' => MembershipStatus::Active,
        ]);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);
        MassAssignment::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'user_id' => $lector->id,
            'assignment_role' => 'lector',
            'status' => MassAssignmentStatus::Active,
        ]);
        $readingList = ReadingList::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => ReadingListStatus::Preparing,
        ]);

        return [$church, $mass, $lector, $readingList];
    }

    private function addConfirmedEntry(Church $church, MassSchedule $mass, ReadingList $readingList): ReadingListEntry
    {
        $intention = $this->confirmedIntention($church, $mass);

        return ReadingListEntry::factory()->create([
            'reading_list_id' => $readingList->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'intention_id' => $intention->id,
            'position' => $readingList->entries()->count() + 1,
        ]);
    }

    private function confirmedIntention(Church $church, MassSchedule $mass): Intention
    {
        $order = Order::factory()->confirmed()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);
        Payment::factory()->verified()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
        ]);

        return Intention::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => IntentionStatus::Confirmed,
        ]);
    }
}
