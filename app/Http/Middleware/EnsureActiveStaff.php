<?php

namespace App\Http\Middleware;

use App\Enums\MembershipRole;
use App\Enums\UserStatus;
use App\Models\Church;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
        }

        // If user is disabled, invalidate session and abort
        if ($user->status === UserStatus::Disabled) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(Response::HTTP_FORBIDDEN, 'Your account is disabled.');
        }

        // Central Admin bypasses church-specific role requirements
        if ($user->isCentralAdmin()) {
            return $next($request);
        }

        if ($requiredRole === 'central_admin') {
            abort(Response::HTTP_FORBIDDEN, 'Central Admin privilege required.');
        }

        // Resolve church if in route parameters
        $church = $request->route('church');
        if (is_numeric($church) || is_string($church)) {
            $church = Church::find($church);
        }

        if ($requiredRole === 'church_admin') {
            if ($church instanceof Church) {
                // If user is not associated with this church, return 404 for tenant isolation
                $hasChurchMembership = $user->churchMemberships()
                    ->active()
                    ->where('church_id', $church->id)
                    ->exists();

                if (! $hasChurchMembership) {
                    abort(Response::HTTP_NOT_FOUND, 'Church not found.');
                }

                // If user has membership in this church but not ChurchAdmin, return 403 Forbidden
                if (! $user->hasRoleInChurch($church, MembershipRole::ChurchAdmin)) {
                    abort(Response::HTTP_FORBIDDEN, 'Church Admin access required.');
                }
            } else {
                // Must have at least one active Church Admin membership
                $hasAnyAdmin = $user->churchMemberships()
                    ->active()
                    ->where('role', MembershipRole::ChurchAdmin->value)
                    ->exists();

                if (! $hasAnyAdmin) {
                    abort(Response::HTTP_FORBIDDEN, 'Church Admin access required.');
                }
            }
        } elseif ($requiredRole === 'lector') {
            if ($church instanceof Church) {
                $hasChurchMembership = $user->churchMemberships()
                    ->active()
                    ->where('church_id', $church->id)
                    ->exists();

                if (! $hasChurchMembership) {
                    abort(Response::HTTP_NOT_FOUND, 'Church not found.');
                }

                if (! $user->hasRoleInChurch($church, MembershipRole::Lector) && ! $user->hasRoleInChurch($church, MembershipRole::ChurchAdmin)) {
                    abort(Response::HTTP_FORBIDDEN, 'Lector access required.');
                }
            } else {
                $hasAnyLector = $user->churchMemberships()
                    ->active()
                    ->where('role', MembershipRole::Lector->value)
                    ->exists();

                if (! $hasAnyLector) {
                    abort(Response::HTTP_FORBIDDEN, 'Lector access required.');
                }
            }
        } else {
            // General staff: must have at least one active membership
            $hasActiveMembership = $user->churchMemberships()->active()->exists();

            if (! $hasActiveMembership) {
                abort(Response::HTTP_FORBIDDEN, 'Active staff membership required.');
            }
        }

        return $next($request);
    }
}
