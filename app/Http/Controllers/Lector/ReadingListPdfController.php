<?php

namespace App\Http\Controllers\Lector;

use App\Enums\IntentionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListEntryStatus;
use App\Enums\ReadingListStatus;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\MassSchedule;
use App\Models\ReadingList;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadingListPdfController extends Controller
{
    public function download(Request $request, Church $church, MassSchedule $mass): Response
    {
        if ($mass->church_id !== $church->id) {
            abort(404);
        }

        $readingList = $mass->readingList()->firstOrFail();
        if (! $request->user()->can('view', $readingList)) {
            abort(404);
        }

        if ($readingList->entries()->where('status', ReadingListEntryStatus::IssueReported->value)->exists()) {
            abort(409, __('Resolve all reported reading-list issues before downloading the PDF.'));
        }

        $entries = $this->entriesForPdf($readingList);
        $timezone = $mass->timezone ?: $church->timezone;

        $pdf = Pdf::loadView('lector.reading-list-pdf', [
            'churchName' => $church->name,
            'massTitle' => $mass->title,
            'massDateTime' => $mass->starts_at->copy()->setTimezone($timezone)->format('F j, Y g:i A T'),
            'locationLabel' => $mass->location_label,
            'entries' => $entries,
            'version' => $readingList->version,
            'generatedAt' => now('UTC')->format('Y-m-d H:i:s'),
        ])
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isPhpEnabled' => false,
                'isJavascriptEnabled' => false,
            ]);

        return $pdf->download("reading-list-mass-{$mass->id}-v{$readingList->version}.pdf");
    }

    /**
     * @return list<array{id: int, person_or_family_name: string, pronunciation_hint: ?string, wording: string, category_label: ?string}>
     */
    private function entriesForPdf(ReadingList $readingList): array
    {
        if (in_array($readingList->status, [ReadingListStatus::Finalized, ReadingListStatus::Completed], true)) {
            $version = $readingList->versions()
                ->where('version', $readingList->version)
                ->first();

            if (! $version) {
                abort(500, 'The finalized reading-list snapshot is missing.');
            }

            $snapshot = $version->snapshot;
            if (
                ! is_array($snapshot)
                || (int) ($snapshot['church_id'] ?? 0) !== $readingList->church_id
                || (int) ($snapshot['mass_schedule_id'] ?? 0) !== $readingList->mass_schedule_id
                || (int) ($snapshot['version'] ?? 0) !== $readingList->version
                || ! is_array($snapshot['entries'] ?? null)
            ) {
                abort(500, 'The finalized reading-list snapshot is invalid.');
            }

            return array_map(function ($entry) {
                if (
                    ! is_array($entry)
                    || ! isset($entry['id'], $entry['person_or_family_name'], $entry['wording'])
                    || ! is_string($entry['person_or_family_name'])
                    || ! is_string($entry['wording'])
                ) {
                    abort(500, 'The finalized reading-list snapshot contains an invalid entry.');
                }

                return [
                    'id' => (int) $entry['id'],
                    'person_or_family_name' => $entry['person_or_family_name'],
                    'pronunciation_hint' => isset($entry['pronunciation_hint']) && is_string($entry['pronunciation_hint'])
                        ? $entry['pronunciation_hint']
                        : null,
                    'wording' => $entry['wording'],
                    'category_label' => isset($entry['category_label']) && is_string($entry['category_label'])
                        ? $entry['category_label']
                        : null,
                ];
            }, $snapshot['entries']);
        }

        return $readingList->entries()
            ->where('status', '!=', ReadingListEntryStatus::Excluded->value)
            ->with('intention.order.payments')
            ->orderBy('position')
            ->get()
            ->map(function ($entry) use ($readingList) {
                $intention = $entry->intention;

                if (
                    ! $intention
                    || $entry->church_id !== $readingList->church_id
                    || $entry->mass_schedule_id !== $readingList->mass_schedule_id
                    || $intention->church_id !== $readingList->church_id
                    || $intention->mass_schedule_id !== $readingList->mass_schedule_id
                    || ! $intention->order
                    || ! $intention->order->payments->contains(
                        fn ($payment) => $payment->status === PaymentStatus::Verified
                    )
                    || ! in_array($intention->status, [
                        IntentionStatus::Confirmed,
                        IntentionStatus::CorrectionPending,
                        IntentionStatus::Completed,
                    ], true)
                ) {
                    abort(500, 'The reading list contains an invalid intention entry.');
                }

                return [
                    'id' => $entry->id,
                    'person_or_family_name' => $intention->person_or_family_name,
                    'pronunciation_hint' => $intention->pronunciation_hint,
                    'wording' => $intention->wording,
                    'category_label' => $intention->category_label_snapshot,
                ];
            })
            ->values()
            ->all();
    }
}
