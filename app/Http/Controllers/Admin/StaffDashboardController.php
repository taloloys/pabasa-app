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
        $churchesCount = Church::count();
        $publishedChurchesCount = Church::where('status', ChurchStatus::Published->value)->count();
        $pendingChurchesCount = Church::where('status', ChurchStatus::PendingVerification->value)->count();
        $suspendedChurchesCount = Church::where('status', ChurchStatus::Suspended->value)->count();
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
