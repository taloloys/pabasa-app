<?php

namespace App\Http\Controllers\Church;

use App\Actions\Churches\RevokeMembershipAction;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\ChurchMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class MembershipController extends Controller
{
    public function index(Request $request, Church $church): Response|JsonResponse
    {
        Gate::authorize('viewAny', [ChurchMembership::class, $church]);

        $memberships = $church->memberships()
            ->with(['user', 'inviter'])
            ->latest()
            ->get();

        $invitations = $church->invitations()
            ->with('inviter')
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'church' => $church,
                'memberships' => $memberships,
                'invitations' => $invitations,
            ]);
        }

        return Inertia::render('Church/Memberships/Index', [
            'church' => $church,
            'memberships' => $memberships,
            'invitations' => $invitations,
        ]);
    }

    public function destroy(
        Request $request,
        Church $church,
        ChurchMembership $membership,
        RevokeMembershipAction $revokeAction
    ): JsonResponse|HttpResponse {
        if ($membership->church_id !== $church->id) {
            abort(HttpResponse::HTTP_NOT_FOUND, 'Membership not found.');
        }

        Gate::authorize('revoke', $membership);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $revokeAction->execute(
            membership: $membership,
            revokedBy: $request->user(),
            reason: $validated['reason'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Membership revoked successfully.',
            ]);
        }

        return back()->with('success', 'Membership revoked successfully.');
    }
}
