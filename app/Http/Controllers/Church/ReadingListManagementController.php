<?php

namespace App\Http\Controllers\Church;

use App\Actions\ReadingLists\ResolveReadingListEntryIssueAction;
use App\Actions\ReadingLists\UpdateIntentionPronunciationHintAction;
use App\Enums\IntentionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListEntryIssueStatus;
use App\Enums\ReadingListIssueResolution;
use App\Enums\ReadingListStatus;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\MassSchedule;
use App\Models\ReadingListEntry;
use App\Models\ReadingListEntryIssue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReadingListManagementController extends Controller
{
    public function show(Request $request, Church $church, MassSchedule $mass): Response
    {
        $this->authorize('update', $church);

        if ($mass->church_id !== $church->id) {
            abort(404);
        }

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
                ->orderBy('position')
                ->get()
            : collect();

        return Inertia::render('Church/Masses/ReadingList', [
            'church' => ['id' => $church->id, 'name' => $church->name],
            'mass' => ['id' => $mass->id, 'title' => $mass->title, 'starts_at' => $mass->starts_at],
            'readingList' => $readingList ? [
                'id' => $readingList->id,
                'status' => $readingList->status->value,
            ] : null,
            'entries' => $entries->map(fn (ReadingListEntry $entry) => [
                'id' => $entry->id,
                'name' => $entry->intention->person_or_family_name,
                'pronunciationHint' => $entry->intention->pronunciation_hint,
                'issue' => $entry->issues->first() ? [
                    'id' => $entry->issues->first()->id,
                    'privateNote' => $entry->issues->first()->private_note,
                ] : null,
            ])->values(),
            'canEditHints' => $readingList !== null
                && in_array($readingList->status, [ReadingListStatus::Preparing, ReadingListStatus::Reopened], true),
            'canResolveIssues' => $readingList !== null
                && in_array($readingList->status, [ReadingListStatus::Preparing, ReadingListStatus::Reopened], true),
        ]);
    }

    public function updatePronunciationHint(
        Request $request,
        Church $church,
        MassSchedule $mass,
        ReadingListEntry $entry,
        UpdateIntentionPronunciationHintAction $action,
    ): RedirectResponse {
        $this->authorize('update', $church);

        if ($mass->church_id !== $church->id || $entry->mass_schedule_id !== $mass->id) {
            abort(404);
        }

        $readingList = $mass->readingList()->firstOrFail();
        if ($entry->reading_list_id !== $readingList->id) {
            abort(404);
        }

        $data = $request->validate([
            'pronunciation_hint' => ['nullable', 'string', 'max:255'],
        ]);

        $action->execute($readingList, $entry->intention, $request->user(), $data['pronunciation_hint']);

        return back()->with('success', __('Pronunciation hint saved.'));
    }

    public function resolveIssue(
        Request $request,
        Church $church,
        MassSchedule $mass,
        ReadingListEntry $entry,
        ReadingListEntryIssue $issue,
        ResolveReadingListEntryIssueAction $action,
    ): RedirectResponse {
        if ($mass->church_id !== $church->id || $entry->mass_schedule_id !== $mass->id) {
            abort(404);
        }

        $readingList = $mass->readingList()->firstOrFail();
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
}
