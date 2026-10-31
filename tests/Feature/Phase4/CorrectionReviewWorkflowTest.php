<?php

namespace Tests\Feature\Phase4;

use App\Actions\ReadingLists\FinalizeReadingListAction;
use App\Actions\ReadingLists\MarkReadingEntryCompletedAction;
use App\Actions\ReadingLists\ReopenReadingListAction;
use App\Actions\Requests\ApproveCorrectionRequestAction;
use App\Actions\Requests\CreateCorrectionRequestAction;
use App\Actions\Requests\RejectCorrectionRequestAction;
use App\Enums\CorrectionRequestStatus;
use App\Enums\IntentionStatus;
use App\Enums\MassAssignmentStatus;
use App\Enums\MembershipStatus;
use App\Enums\ReadingListStatus;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\Intention;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\ReadingListVersion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CorrectionReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_a_correction_preserves_current_text_and_marks_confirmed_intention_pending(): void
    {
        [$church, $mass, $owner, $lector, $order, $intention, $readingList] = $this->correctionFixture();

        $correction = app(CreateCorrectionRequestAction::class)->execute($order, $intention, [
            'person_or_family_name' => 'Updated family name',
            'wording' => 'Updated intention wording',
        ], $owner);

        $this->assertSame('Original family name', $intention->refresh()->person_or_family_name);
        $this->assertSame('Original intention wording', $intention->wording);
        $this->assertSame(IntentionStatus::CorrectionPending, $intention->status);
        $this->assertSame('Original family name', $correction->old_values['person_or_family_name']);
        $this->assertSame('Original intention wording', $correction->old_values['wording']);
        $this->assertSame(ReadingListStatus::Preparing, $readingList->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request.correction_submitted',
            'church_id' => $church->id,
            'actor_user_id' => $owner->id,
        ]);

        try {
            app(CreateCorrectionRequestAction::class)->execute($order, $intention, [
                'person_or_family_name' => 'Another name',
                'wording' => 'Another wording',
            ], $owner);
            $this->fail('A second open correction request for the same intention must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('intention_id', $exception->errors());
        }
    }

    public function test_central_admin_applies_correction_only_after_reopen_and_re_finalization_preserves_snapshots(): void
    {
        [$church, $mass, $owner, $lector, $order, $intention, $readingList] = $this->correctionFixture();
        app(FinalizeReadingListAction::class)->execute($readingList, $lector);
        $originalSnapshot = ReadingListVersion::query()
            ->where('reading_list_id', $readingList->id)
            ->where('version', 1)
            ->firstOrFail()
            ->snapshot;

        $correction = app(CreateCorrectionRequestAction::class)->execute($order, $intention, [
            'person_or_family_name' => 'Corrected family name',
            'wording' => 'Corrected intention wording',
        ], $owner);
        $admin = User::factory()->centralAdmin()->create();

        try {
            app(ApproveCorrectionRequestAction::class)->execute($correction, $admin, 'Verified against the request.');
            $this->fail('A finalized reading list must be reopened before applying a correction.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('correction', $exception->errors());
        }

        $this->assertSame('Original intention wording', $intention->refresh()->wording);
        $this->assertSame(CorrectionRequestStatus::Submitted, $correction->refresh()->status);

        app(ReopenReadingListAction::class)->execute($readingList, $lector, 'Approved correction requires a new list version.');
        app(ApproveCorrectionRequestAction::class)->execute($correction, $admin, 'Verified against the request.');

        $this->assertSame('Corrected family name', $intention->refresh()->person_or_family_name);
        $this->assertSame('Corrected intention wording', $intention->wording);
        $this->assertSame(IntentionStatus::Confirmed, $intention->status);
        $this->assertSame(CorrectionRequestStatus::Approved, $correction->refresh()->status);
        $this->assertSame($admin->id, $correction->reviewed_by);
        $this->assertSame('Verified against the request.', $correction->decision_reason);

        app(FinalizeReadingListAction::class)->execute($readingList, $lector);
        $versions = ReadingListVersion::query()
            ->where('reading_list_id', $readingList->id)
            ->orderBy('version')
            ->get();

        $this->assertCount(2, $versions);
        $this->assertSame($originalSnapshot, $versions[0]->snapshot);
        $this->assertSame('Corrected intention wording', $versions[1]->snapshot['entries'][0]['wording']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request.correction_approved',
            'church_id' => $church->id,
            'actor_user_id' => $admin->id,
            'reason' => 'Verified against the request.',
        ]);
    }

    public function test_completed_reading_list_correction_cannot_be_approved(): void
    {
        [, , $owner, $lector, $order, $intention, $readingList] = $this->correctionFixture();
        $correction = app(CreateCorrectionRequestAction::class)->execute($order, $intention, [
            'person_or_family_name' => 'Corrected family name',
            'wording' => 'Corrected intention wording',
        ], $owner);

        app(FinalizeReadingListAction::class)->execute($readingList, $lector);
        app(MarkReadingEntryCompletedAction::class)->execute(
            $readingList,
            $readingList->entries()->firstOrFail(),
            $lector,
        );

        try {
            app(ApproveCorrectionRequestAction::class)->execute(
                $correction,
                User::factory()->centralAdmin()->create(),
                'This must not be applied.',
            );
            $this->fail('A completed reading list must reject correction approval.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('correction', $exception->errors());
        }

        $this->assertSame(ReadingListStatus::Completed, $readingList->refresh()->status);
        $this->assertSame('Original intention wording', $intention->refresh()->wording);
        $this->assertSame(CorrectionRequestStatus::Submitted, $correction->refresh()->status);
    }

    public function test_rejection_restores_confirmed_state_without_changing_content(): void
    {
        [, , $owner, , $order, $intention] = $this->correctionFixture();
        $correction = app(CreateCorrectionRequestAction::class)->execute($order, $intention, [
            'person_or_family_name' => 'Corrected family name',
            'wording' => 'Corrected intention wording',
        ], $owner);
        $admin = User::factory()->centralAdmin()->create();

        app(RejectCorrectionRequestAction::class)->execute($correction, $admin, 'The evidence does not support this request.');

        $this->assertSame('Original family name', $intention->refresh()->person_or_family_name);
        $this->assertSame('Original intention wording', $intention->wording);
        $this->assertSame(IntentionStatus::Confirmed, $intention->status);
        $this->assertSame(CorrectionRequestStatus::Rejected, $correction->refresh()->status);
        $this->assertSame($admin->id, $correction->reviewed_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'request.correction_rejected',
            'actor_user_id' => $admin->id,
            'reason' => 'The evidence does not support this request.',
        ]);
    }

    public function test_approval_rejects_stale_correction_snapshots(): void
    {
        [, , $owner, , $order, $intention] = $this->correctionFixture();
        $correction = app(CreateCorrectionRequestAction::class)->execute($order, $intention, [
            'person_or_family_name' => 'Corrected family name',
            'wording' => 'Corrected intention wording',
        ], $owner);
        $admin = User::factory()->centralAdmin()->create();

        $intention->update(['wording' => 'Different current wording']);

        try {
            app(ApproveCorrectionRequestAction::class)->execute($correction, $admin, 'Reviewed request.');
            $this->fail('An approval must not overwrite content changed after submission.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('correction', $exception->errors());
        }

        $this->assertSame('Different current wording', $intention->refresh()->wording);
        $this->assertSame(CorrectionRequestStatus::Submitted, $correction->refresh()->status);
    }

    public function test_correction_review_queue_is_central_admin_only(): void
    {
        [, , $owner, , $order, $intention] = $this->correctionFixture();
        app(CreateCorrectionRequestAction::class)->execute($order, $intention, [
            'person_or_family_name' => 'Updated family name',
            'wording' => 'Updated intention wording',
        ], $owner);

        $ordinaryUser = User::factory()->create();

        $this->actingAs($ordinaryUser)
            ->get(route('admin.corrections.index'))
            ->assertForbidden();

        $admin = User::factory()->centralAdmin()->create();
        $this->actingAs($admin)
            ->get(route('admin.corrections.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Corrections/ReviewQueue')
                ->where('corrections.total', 1)
                ->where('corrections.data.0.old_values.wording', 'Original intention wording')
                ->where('corrections.data.0.requested_values.wording', 'Updated intention wording'));
    }

    public function test_approval_action_rejects_non_central_admin_even_if_called_directly(): void
    {
        [, , $owner, , $order, $intention] = $this->correctionFixture();
        $correction = app(CreateCorrectionRequestAction::class)->execute($order, $intention, [
            'person_or_family_name' => 'Updated family name',
            'wording' => 'Updated intention wording',
        ], $owner);

        $this->expectException(AuthorizationException::class);
        app(ApproveCorrectionRequestAction::class)->execute($correction, User::factory()->create(), 'Not authorized.');
    }

    private function correctionFixture(): array
    {
        $church = Church::factory()->create();
        $owner = User::factory()->create();
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
        $order = Order::factory()->confirmed()->create([
            'user_id' => $owner->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
        ]);
        $intention = Intention::factory()->create([
            'order_id' => $order->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'person_or_family_name' => 'Original family name',
            'wording' => 'Original intention wording',
            'status' => IntentionStatus::Confirmed,
        ]);
        $readingList = ReadingList::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => ReadingListStatus::Preparing,
        ]);
        ReadingListEntry::factory()->create([
            'reading_list_id' => $readingList->id,
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'intention_id' => $intention->id,
            'position' => 1,
        ]);

        return [$church, $mass, $owner, $lector, $order, $intention, $readingList];
    }
}
