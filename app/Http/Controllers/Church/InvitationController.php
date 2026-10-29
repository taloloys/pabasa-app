<?php

namespace App\Http\Controllers\Church;

use App\Actions\Churches\AcceptInvitationAction;
use App\Actions\Churches\CreateInvitationAction;
use App\Actions\Churches\RevokeInvitationAction;
use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvitationController extends Controller
{
    public function store(
        Request $request,
        Church $church,
        CreateInvitationAction $createAction
    ): JsonResponse|HttpResponse {
        Gate::authorize('create', [ChurchMembership::class, $church]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(MembershipRole::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $createAction->execute(
            church: $church,
            email: $validated['email'],
            role: $validated['role'],
            invitedBy: $request->user(),
            reason: $validated['reason'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Invitation created successfully.',
                'invitation_id' => $result['invitation']->id,
                'raw_token' => $result['raw_token'],
            ], HttpResponse::HTTP_CREATED);
        }

        return back()->with([
            'success' => 'Invitation created successfully.',
            'raw_token' => $result['raw_token'],
        ]);
    }

    public function revoke(
        Request $request,
        Church $church,
        Invitation $invitation,
        RevokeInvitationAction $revokeAction
    ): JsonResponse|HttpResponse {
        if ($invitation->church_id !== $church->id) {
            abort(HttpResponse::HTTP_NOT_FOUND, 'Invitation not found.');
        }

        Gate::authorize('create', [ChurchMembership::class, $church]);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $revokeAction->execute(
            invitation: $invitation,
            revokedBy: $request->user(),
            reason: $validated['reason'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Invitation revoked successfully.',
            ]);
        }

        return back()->with('success', 'Invitation revoked successfully.');
    }

    public function showAccept(string $token): Response|JsonResponse
    {
        $tokenHash = Invitation::hashToken($token);
        $invitation = Invitation::with('church')->where('token_hash', $tokenHash)->first();

        if (! $invitation || ! $invitation->isPending()) {
            abort(HttpResponse::HTTP_NOT_FOUND, 'Invitation not found or no longer active.');
        }

        return Inertia::render('Auth/AcceptInvitation', [
            'token' => $token,
            'email' => $invitation->email,
            'church' => $invitation->church,
            'role' => $invitation->role->value,
        ]);
    }

    public function accept(
        Request $request,
        AcceptInvitationAction $acceptAction
    ): JsonResponse|HttpResponse {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $user = $request->user();
        if (! $user) {
            abort(HttpResponse::HTTP_UNAUTHORIZED, 'Authentication required to accept invitation.');
        }

        $membership = $acceptAction->execute(
            rawToken: $validated['token'],
            user: $user
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Invitation accepted successfully.',
                'membership_id' => $membership->id,
            ]);
        }

        return redirect()->route('church.dashboard', ['church' => $membership->church_id])
            ->with('success', 'Welcome! Your membership is now active.');
    }
}
