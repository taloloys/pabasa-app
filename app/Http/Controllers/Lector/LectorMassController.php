<?php

namespace App\Http\Controllers\Lector;

use App\Actions\Masses\CloseMassBookingEarlyAction;
use App\Actions\ReadingLists\CorrectReadingEntryCompletionAction;
use App\Actions\ReadingLists\FinalizeReadingListAction;
use App\Actions\ReadingLists\MarkReadingEntryCompletedAction;
use App\Actions\ReadingLists\ReopenReadingListAction;
use App\Actions\ReadingLists\ReorderReadingListAction;
use App\Actions\ReadingLists\ReportReadingListEntryIssueAction;
use App\Actions\ReadingLists\ResolveReadingListEntryIssueAction;
use App\Enums\IntentionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListEntryIssueStatus;
use App\Enums\ReadingListIssueResolution;
use App\Enums\ReadingListStatus;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\Intention;
use App\Models\MassSchedule;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\ReadingListEntryIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LectorMassController extends Controller
{
    public function index(Request $request, Church $church): Response
    {
        $user = $request->user();

        // Fetch only masses actively assigned to this lector
        $assignedMasses = MassSchedule::query()
            ->with(['category'])
            ->where('church_id', $church->id)
            ->whereHas('activeAssignments', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('assignment_role', 'lector');
            })
            ->orderBy('starts_at', 'desc')
            ->paginate(15);

        return Inertia::render('Lector/Masses/Index', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'timezone' => $church->timezone,
            ],
            'assignedMasses' => $assignedMasses,
        ]);
    }

    public function show(Request $request, Church $church, MassSchedule $mass): Response
    {
        if ($mass->church_id !== $church->id) {
            abort(404, 'Mass not found.');
        }

        // Rule 13: Lector cannot view unassigned Mass details by changing URL ID
        if (! $request->user()->can('view', $mass)) {
            abort(404, 'Mass assignment not found.');
        }

        $mass->load(['category', 'activeAssignments.user']);
        $readingList = $mass->readingList()->first();
        $entries = $readingList
            ? $readingList->entries()
                ->whereHas('intention', fn ($query) => $query
                    ->whereIn('status', [
                        IntentionStatus::Confirmed->value,
                        IntentionStatus::CorrectionPending->value,
                        IntentionStatus::Completed->value,
                    ])
                    ->whereHas('order.payments', fn ($paymentQuery) => $paymentQuery
                        ->where('status', PaymentStatus::Verified->value)))
                ->with([
                    'intention',
                    'issues' => fn ($query) => $query
                        ->where('status', ReadingListEntryIssueStatus::Open->value)
                        ->latest('reported_at'),
                ])
                ->get()
            : collect();
        $currentSnapshot = null;
        if (
            $readingList
            && in_array($readingList->status, [ReadingListStatus::Finalized, ReadingListStatus::Completed], true)
        ) {
            $currentSnapshot = $readingList->versions()
                ->where('version', $readingList->version)
                ->value('snapshot');
        }
        $snapshotHints = collect(is_array($currentSnapshot) ? ($currentSnapshot['entries'] ?? []) : [])
            ->keyBy('id');
        $pendingIntentions = Intention::query()
            ->where('church_id', $church->id)
            ->where('mass_schedule_id', $mass->id)
            ->where('status', IntentionStatus::Confirmed->value)
            ->whereHas('order.payments', fn ($query) => $query->where('status', PaymentStatus::Verified->value))
            ->whereDoesntHave('readingListEntry')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('Lector/Masses/Show', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'timezone' => $church->timezone,
            ],
            'mass' => [
                'id' => $mass->id,
                'title' => $mass->title,
                'starts_at' => $mass->starts_at,
                'booking_status' => $mass->booking_status->value,
                'status' => $mass->status->value,
                'booking_cutoff_at' => $mass->booking_cutoff_at,
                'finalization_deadline' => $mass->finalization_deadline,
                'timezone' => $mass->timezone ?: $church->timezone,
                'location_label' => $mass->location_label,
                'language' => $mass->language,
            ],
            'readingList' => $readingList ? [
                'id' => $readingList->id,
                'status' => $readingList->status->value,
                'version' => $readingList->version,
                'entries' => $entries->map(fn (ReadingListEntry $entry) => [
                    'id' => $entry->id,
                    'position' => $entry->position,
                    'status' => $entry->status->value,
                    'name' => $entry->intention->person_or_family_name,
                    'wording' => $entry->intention->wording,
                    'category' => $entry->intention->category_label_snapshot,
                    'pronunciationHint' => $snapshotHints->has($entry->id)
                        ? ($snapshotHints->get($entry->id)['pronunciation_hint'] ?? null)
                        : (in_array($readingList->status, [ReadingListStatus::Preparing, ReadingListStatus::Reopened], true)
                            ? $entry->intention->pronunciation_hint
                            : null),
                    'issueNote' => $entry->issues->first()?->private_note,
                    'issueId' => $entry->issues->first()?->id,
                ])->values(),
            ] : null,
            'pendingIntentions' => $pendingIntentions->map(fn (Intention $intention) => [
                'id' => $intention->id,
                'name' => $intention->person_or_family_name,
                'wording' => $intention->wording,
                'category' => $intention->category_label_snapshot,
            ])->values(),
            'canManageReadingList' => $readingList !== null
                && $request->user()->can('reorder', $readingList),
        ]);
    }

    public function reorder(Request $request, Church $church, MassSchedule $mass, ReorderReadingListAction $action): RedirectResponse
    {
        $readingList = $this->assignedReadingList($request, $church, $mass);
        $data = $request->validate([
            'entry_ids' => ['present', 'array'],
            'entry_ids.*' => ['required', 'integer', 'min:1'],
        ]);

        $action->execute($readingList, $request->user(), $data['entry_ids']);

        return back()->with('success', __('Reading list order saved.'));
    }

    public function finalize(Request $request, Church $church, MassSchedule $mass, FinalizeReadingListAction $action): RedirectResponse
    {
        $readingList = $this->assignedReadingList($request, $church, $mass);
        $action->execute($readingList, $request->user());

        return back()->with('success', __('Reading list finalized.'));
    }

    public function reopen(Request $request, Church $church, MassSchedule $mass, ReopenReadingListAction $action): RedirectResponse
    {
        $readingList = $this->assignedReadingList($request, $church, $mass);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $action->execute($readingList, $request->user(), $data['reason']);

        return back()->with('success', __('Reading list reopened with an audit reason.'));
    }

    public function completeEntry(
        Request $request,
        Church $church,
        MassSchedule $mass,
        ReadingListEntry $entry,
        MarkReadingEntryCompletedAction $action,
    ): RedirectResponse {
        $readingList = $this->assignedReadingList($request, $church, $mass);

        if ($entry->reading_list_id !== $readingList->id) {
            abort(404);
        }

        $action->execute($readingList, $entry, $request->user());

        return back()->with('success', __('Intention marked as read.'));
    }

    public function correctEntryCompletion(
        Request $request,
        Church $church,
        MassSchedule $mass,
        ReadingListEntry $entry,
        CorrectReadingEntryCompletionAction $action,
    ): RedirectResponse {
        $readingList = $this->assignedReadingList($request, $church, $mass);

        if ($entry->reading_list_id !== $readingList->id) {
            abort(404);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $action->execute($readingList, $entry, $request->user(), $data['reason']);

        return back()->with('success', __('Reading completion corrected with an audit reason.'));
    }

    public function reportIssue(
        Request $request,
        Church $church,
        MassSchedule $mass,
        ReadingListEntry $entry,
        ReportReadingListEntryIssueAction $action,
    ): RedirectResponse {
        $readingList = $this->assignedReadingList($request, $church, $mass);
        $data = $request->validate([
            'private_note' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        if ($entry->reading_list_id !== $readingList->id) {
            abort(404);
        }

        $action->execute($readingList, $entry, $request->user(), $data['private_note']);

        return back()->with('success', __('The issue was reported for staff review.'));
    }

    public function resolveIssue(
        Request $request,
        Church $church,
        MassSchedule $mass,
        ReadingListEntry $entry,
        ReadingListEntryIssue $issue,
        ResolveReadingListEntryIssueAction $action,
    ): RedirectResponse {
        $readingList = $this->assignedReadingList($request, $church, $mass);

        if ($entry->reading_list_id !== $readingList->id) {
            abort(404);
        }

        $data = $request->validate([
            'resolution' => ['required', 'string', Rule::enum(ReadingListIssueResolution::class)],
        ]);

        $action->execute(
            $readingList,
            $entry,
            $issue,
            $request->user(),
            ReadingListIssueResolution::from($data['resolution']),
        );

        return back()->with('success', __('The reading-list issue was resolved.'));
    }

    public function closeEarly(Request $request, Church $church, MassSchedule $mass, CloseMassBookingEarlyAction $action): RedirectResponse
    {
        if ($mass->church_id !== $church->id) {
            abort(404, 'Mass not found.');
        }

        // Rule 10: Assigned lector can close submissions early
        if (! $request->user()->can('closeEarly', $mass)) {
            abort(403, 'Unauthorized to close submissions for this Mass.');
        }

        $action->execute($mass, $request->user(), $request->input('reason', 'Closed early by assigned lector.'));

        return back()->with('success', 'Submissions closed early.');
    }

    private function assignedReadingList(Request $request, Church $church, MassSchedule $mass): ReadingList
    {
        if ($mass->church_id !== $church->id || ! $request->user()->can('view', $mass)) {
            abort(404, 'Mass not found.');
        }

        $readingList = $mass->readingList()->first();
        if (! $readingList) {
            throw ValidationException::withMessages([
                'reading_list' => __('This Mass does not have a reading list yet.'),
            ]);
        }

        return $readingList;
    }
}
