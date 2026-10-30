<?php

namespace App\Http\Controllers\Church;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Church;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChurchDashboardController extends Controller
{
    public function index(Request $request, Church $church): Response
    {
        // Single-roundtrip conditional aggregation for staff roles
        $roleCounts = $church->memberships()
            ->where('status', MembershipStatus::Active->value)
            ->whereIn('role', [MembershipRole::ChurchAdmin->value, MembershipRole::Lector->value])
            ->selectRaw('
                COALESCE(SUM(role = ?), 0) as admins,
                COALESCE(SUM(role = ?), 0) as lectors
            ', [
                MembershipRole::ChurchAdmin->value,
                MembershipRole::Lector->value,
            ])
            ->first();

        $adminsCount = (int) ($roleCounts?->admins ?? 0);
        $lectorsCount = (int) ($roleCounts?->lectors ?? 0);

        $pendingInvitationsCount = $church->invitations()
            ->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();

        $recentAuditLogs = AuditLog::with('actor')
            ->where('church_id', $church->id)
            ->latest('id')
            ->limit(5)
            ->get();

        return Inertia::render('Church/Dashboard', [
            'church' => $church,
            'stats' => [
                'admins_count' => $adminsCount,
                'lectors_count' => $lectorsCount,
                'pending_invitations_count' => $pendingInvitationsCount,
            ],
            'recent_audit_logs' => $recentAuditLogs,
        ]);
    }
}
