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
        $adminsCount = $church->memberships()
            ->where('role', MembershipRole::ChurchAdmin->value)
            ->where('status', MembershipStatus::Active->value)
            ->count();

        $lectorsCount = $church->memberships()
            ->where('role', MembershipRole::Lector->value)
            ->where('status', MembershipStatus::Active->value)
            ->count();

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
