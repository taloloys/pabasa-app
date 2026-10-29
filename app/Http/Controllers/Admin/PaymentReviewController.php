<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Payments\RejectPaymentAction;
use App\Actions\Payments\VerifyPaymentAction;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReviewController extends Controller
{
    /**
     * Display the Central Admin payment review queue.
     */
    public function index(Request $request): Response
    {
        $status = $request->input('status', 'all');

        $query = Payment::with(['order.church', 'order.massSchedule', 'latestEvidence', 'exceptions'])
            ->orderBy('created_at', 'asc');

        if ($status === 'needs_review') {
            $query->where('status', PaymentStatus::NeedsReview->value);
        } elseif ($status === 'pending_verification') {
            $query->where('status', PaymentStatus::PendingVerification->value);
        } else {
            $query->whereIn('status', [
                PaymentStatus::PendingVerification->value,
                PaymentStatus::NeedsReview->value,
                PaymentStatus::ResolutionRequired->value,
            ]);
        }

        $payments = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Payments/ReviewQueue', [
            'payments' => $payments,
            'currentFilter' => $status,
        ]);
    }

    /**
     * Display split-screen review workbench.
     */
    public function show(Payment $payment): Response
    {
        $payment->load([
            'order.church',
            'order.massSchedule',
            'order.intentions.category',
            'evidence',
            'exceptions',
        ]);

        $duplicateReferenceCount = 0;
        if ($payment->normalized_reference) {
            $duplicateReferenceCount = Payment::where('normalized_reference', $payment->normalized_reference)
                ->where('id', '!=', $payment->id)
                ->count();
        }

        return Inertia::render('Admin/Payments/Workbench', [
            'payment' => $payment,
            'duplicateReferenceCount' => $duplicateReferenceCount,
        ]);
    }

    /**
     * Authorized private streaming of payment screenshot.
     */
    public function streamEvidence(Payment $payment, PaymentEvidence $evidence): StreamedResponse
    {
        // Rule 11 & 14: Evidence storage is private; lector denied access; only authorized staff
        Gate::authorize('viewEvidence', $payment);

        if ($evidence->payment_id !== $payment->id) {
            abort(404);
        }

        if (! Storage::disk('private')->exists($evidence->storage_key)) {
            abort(404, 'Evidence file not found.');
        }

        return Storage::disk('private')->response(
            $evidence->storage_key,
            $evidence->original_filename,
            ['Content-Type' => $evidence->mime_type]
        );
    }

    /**
     * Verify payment.
     */
    public function verify(Request $request, Payment $payment, VerifyPaymentAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'verified_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'force_exception_resolution' => ['nullable', 'boolean'],
        ]);

        $action->execute($payment, $request->user(), $validated);

        return back()->with('success', "Payment #{$payment->id} verified and order confirmed.");
    }

    /**
     * Reject payment.
     */
    public function reject(Request $request, Payment $payment, RejectPaymentAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->execute($payment, $request->user(), $validated['reason']);

        return back()->with('success', "Payment #{$payment->id} rejected.");
    }
}
