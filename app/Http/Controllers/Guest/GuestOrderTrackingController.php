<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Services\GuestAccess\GuestAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuestOrderTrackingController extends Controller
{
    public function __construct(
        protected GuestAccessService $guestAccess,
    ) {}

    /**
     * View order tracking via unguessable guest token.
     */
    public function show(Request $request, string $token): Response
    {
        $access = $this->guestAccess->verifyToken($token);

        // Rule 20 & 21: Guest tracking rejects order number alone and requires valid, unexpired, non-revoked credential
        if (! $access) {
            abort(404, 'Order tracking link is invalid, expired, or revoked.');
        }

        $order = $access->order;

        return Inertia::render('Guest/Orders/Tracking', [
            'token' => $token,
            'isEmailVerified' => $access->isVerified(),
            'maskedEmail' => preg_replace('/(?<=.{2}).(?=.*@)/u', '*', $access->email),
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'expected_amount' => $order->expected_amount,
                'currency' => $order->currency,
                'submitter_name' => $order->submitter_name,
                'submitted_at' => $order->submitted_at?->toIso8601String(),
                'confirmed_at' => $order->confirmed_at?->toIso8601String(),
            ],
            'church' => [
                'name' => $order->church->name,
                'contact_phone' => $order->church->contact_phone,
            ],
            'mass' => [
                'title' => $order->massSchedule->title,
                'starts_at_formatted' => $order->massSchedule->formatInChurchTimezone('starts_at', 'M d, Y h:i A'),
                'cutoff_formatted' => $order->massSchedule->formatInChurchTimezone('booking_cutoff_at', 'M d, Y h:i A'),
            ],
            'intentions' => $order->intentions->map(fn ($i) => [
                'id' => $i->id,
                'name' => $i->person_or_family_name,
                'wording' => $i->wording,
                'category' => $i->category_label_snapshot,
                'status' => $i->status->value,
            ]),
            'payment' => $order->latestPayment ? [
                'status' => $order->latestPayment->status->value,
                'submitted_reference' => $order->latestPayment->submitted_reference,
                'submitted_amount' => $order->latestPayment->submitted_amount,
                'verified_amount' => $order->latestPayment->verified_amount,
            ] : null,
        ]);
    }

    /**
     * Submit email verification challenge code.
     */
    public function verifyChallenge(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'verification_code' => ['required', 'string', 'size:6'],
        ]);

        $access = $this->guestAccess->verifyToken($token);
        if (! $access) {
            abort(404, 'Invalid credential.');
        }

        $success = $this->guestAccess->confirmVerificationCode($access, $validated['verification_code']);

        if (! $success) {
            return back()->withErrors(['verification_code' => 'The verification code is incorrect or expired.']);
        }

        return back()->with('success', 'Email confirmed. You can now view and manage your intentions.');
    }

    /**
     * Request guest recovery link.
     */
    public function recover(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Rule 22: Recovery response does not disclose whether an email exists
        $this->guestAccess->initiateRecovery($validated['email']);

        $message = 'If an order exists for this email address, access recovery instructions have been sent.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
