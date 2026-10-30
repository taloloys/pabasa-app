<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ChurchStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Church;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // Single-roundtrip conditional aggregation for church status metrics
        $churchMetrics = Church::query()
            ->selectRaw('
                COUNT(*) as total,
                COALESCE(SUM(status = ?), 0) as published,
                COALESCE(SUM(status = ?), 0) as pending,
                COALESCE(SUM(status = ?), 0) as suspended
            ', [
                ChurchStatus::Published->value,
                ChurchStatus::PendingVerification->value,
                ChurchStatus::Suspended->value,
            ])
            ->first();

        $churchesCount = (int) ($churchMetrics?->total ?? 0);
        $publishedChurchesCount = (int) ($churchMetrics?->published ?? 0);
        $pendingChurchesCount = (int) ($churchMetrics?->pending ?? 0);
        $suspendedChurchesCount = (int) ($churchMetrics?->suspended ?? 0);
        $usersCount = User::count();

        $recentChurches = Church::latest()->limit(5)->get();
        $recentAuditLogs = AuditLog::with('actor')->latest('id')->limit(8)->get();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'churches_count' => $churchesCount,
                'published_churches' => $publishedChurchesCount,
                'pending_churches' => $pendingChurchesCount,
                'suspended_churches' => $suspendedChurchesCount,
                'users_count' => $usersCount,
            ],
            'recent_churches' => $recentChurches,
            'recent_audit_logs' => $recentAuditLogs,
        ]);
    }
}
