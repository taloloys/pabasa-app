<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Requests\ApproveCorrectionRequestAction;
use App\Actions\Requests\RejectCorrectionRequestAction;
use App\Enums\CorrectionRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CorrectionReviewController extends Controller
{
    public function index(): Response
    {
        $corrections = CorrectionRequest::query()
            ->with([
                'order.church',
                'order.massSchedule',
                'intention.readingListEntry.readingList',
            ])
            ->whereIn('status', [
                CorrectionRequestStatus::Submitted->value,
                CorrectionRequestStatus::UnderReview->value,
            ])
            ->orderBy('created_at')
            ->paginate(20)
            ->through(function (CorrectionRequest $correction) {
                $readingList = $correction->intention?->readingListEntry?->readingList;

                return [
                    'id' => $correction->id,
                    'status' => $correction->status->value,
                    'created_at' => $correction->created_at,
                    'church_name' => $correction->order?->church?->name,
                    'mass_title' => $correction->order?->massSchedule?->title,
                    'mass_starts_at' => $correction->order?->massSchedule?->starts_at,
                    'order_number' => $correction->order?->order_number,
                    'old_values' => $correction->old_values,
                    'requested_values' => $correction->requested_values,
                    'list_status' => $readingList?->status?->value,
                    'list_version' => $readingList?->version,
                ];
            });

        return Inertia::render('Admin/Corrections/ReviewQueue', [
            'corrections' => $corrections,
        ]);
    }

    public function approve(
        Request $request,
        CorrectionRequest $correction,
        ApproveCorrectionRequestAction $action,
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->execute($correction, $request->user(), $validated['reason']);

        return back()->with('success', __('Correction approved and applied.'));
    }

    public function reject(
        Request $request,
        CorrectionRequest $correction,
        RejectCorrectionRequestAction $action,
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->execute($correction, $request->user(), $validated['reason']);

        return back()->with('success', __('Correction rejected with an audit reason.'));
    }
}
