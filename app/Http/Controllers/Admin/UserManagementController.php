<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Churches\DeactivateUserAction;
use App\Actions\Churches\RevokeMembershipAction;
use App\Actions\Users\ActivateUserAction;
use App\Actions\Users\AssignUserRoleAction;
use App\Actions\Users\CreateUserAction;
use App\Actions\Users\ToggleCentralAdminAction;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRoleRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class UserManagementController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query()
            ->with(['churchMemberships' => function ($q) {
                $q->with('church:id,name,slug');
            }])
            ->latest('id');

        // Search filter
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Status filter
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Church filter
        if ($churchId = $request->input('church_id')) {
            $query->whereHas('churchMemberships', function ($q) use ($churchId) {
                $q->where('church_id', $churchId)
                    ->where('status', MembershipStatus::Active->value);
            });
        }

        // Role filter
        if ($role = $request->input('role')) {
            if ($role === 'central_admin') {
                $query->where('is_central_admin', true);
            } elseif ($role === 'church_admin') {
                $query->whereHas('churchMemberships', function ($q) {
                    $q->where('role', MembershipRole::ChurchAdmin->value)
                        ->where('status', MembershipStatus::Active->value);
                });
            } elseif ($role === 'lector') {
                $query->whereHas('churchMemberships', function ($q) {
                    $q->where('role', MembershipRole::Lector->value)
                        ->where('status', MembershipStatus::Active->value);
                });
            } elseif ($role === 'parishioner') {
                $query->where('is_central_admin', false)
                    ->whereDoesntHave('churchMemberships', function ($q) {
                        $q->where('status', MembershipStatus::Active->value);
                    });
            }
        }

        $users = $query->paginate(15)->withQueryString();

        $churches = Church::select('id', 'name', 'city', 'province')
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'users' => $users,
                'churches' => $churches,
                'filters' => $request->only(['search', 'role', 'status', 'church_id']),
            ]);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'churches' => $churches,
            'filters' => $request->only(['search', 'role', 'status', 'church_id']),
        ]);
    }

    public function store(StoreUserRequest $request, CreateUserAction $createUserAction): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $user = $createUserAction->execute(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'] ?? null,
            isCentralAdmin: (bool) ($validated['is_central_admin'] ?? false),
            churchId: isset($validated['church_id']) ? (int) $validated['church_id'] : null,
            churchRole: $validated['church_role'] ?? null,
            emailVerified: (bool) ($validated['email_verified'] ?? true),
            actor: $request->user(),
            reason: $validated['reason'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'User created successfully.',
                'user' => $user->load('churchMemberships.church'),
            ], HttpResponse::HTTP_CREATED);
        }

        return back()->with('success', "User account for {$user->name} created successfully.");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $updateData = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = bcrypt($validated['password']);
        }

        $user->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'User updated successfully.',
                'user' => $user,
            ]);
        }

        return back()->with('success', 'User profile updated successfully.');
    }

    public function toggleStatus(
        Request $request,
        User $user,
        DeactivateUserAction $deactivateAction,
        ActivateUserAction $activateAction
    ): RedirectResponse|JsonResponse {
        Gate::authorize('toggleStatus', $user);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,disabled'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['status'] === UserStatus::Disabled->value) {
            $deactivateAction->execute(
                user: $user,
                deactivatedBy: $request->user(),
                reason: $validated['reason'] ?? null
            );
            $message = "User {$user->name} disabled successfully.";
        } else {
            $activateAction->execute(
                user: $user,
                activatedBy: $request->user(),
                reason: $validated['reason'] ?? null
            );
            $message = "User {$user->name} activated successfully.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'user' => $user->fresh(),
            ]);
        }

        return back()->with('success', $message);
    }

    public function assignRole(
        AssignRoleRequest $request,
        User $user,
        AssignUserRoleAction $assignRoleAction,
        ToggleCentralAdminAction $toggleAdminAction
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        if ($validated['role_type'] === 'central_admin') {
            $isCentralAdmin = (bool) ($validated['is_central_admin'] ?? false);
            $toggleAdminAction->execute(
                targetUser: $user,
                isCentralAdmin: $isCentralAdmin,
                actor: $request->user(),
                reason: $validated['reason'] ?? null
            );
            $message = $isCentralAdmin
                ? "Central Admin authority granted to {$user->name}."
                : "Central Admin authority revoked from {$user->name}.";
        } else {
            $membership = $assignRoleAction->execute(
                targetUser: $user,
                churchId: (int) $validated['church_id'],
                role: $validated['role'],
                actor: $request->user(),
                reason: $validated['reason'] ?? null
            );
            $message = "Assigned {$membership->role->value} role in {$membership->church->name} to {$user->name}.";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'user' => $user->fresh(['churchMemberships.church']),
            ]);
        }

        return back()->with('success', $message);
    }

    public function revokeRole(
        Request $request,
        User $user,
        ChurchMembership $membership,
        RevokeMembershipAction $revokeMembershipAction
    ): RedirectResponse|JsonResponse {
        Gate::authorize('manageRoles', $user);

        if ($membership->user_id !== $user->id) {
            abort(HttpResponse::HTTP_NOT_FOUND, 'Membership does not belong to this user.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $revokeMembershipAction->execute(
            membership: $membership,
            revokedBy: $request->user(),
            reason: $validated['reason'] ?? null
        );

        $message = "Revoked {$membership->role->value} role for {$user->name}.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'user' => $user->fresh(['churchMemberships.church']),
            ]);
        }

        return back()->with('success', $message);
    }
}
