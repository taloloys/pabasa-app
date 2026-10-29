<?php

namespace App\Actions\Users;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateUserAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(
        string $name,
        string $email,
        ?string $password = null,
        bool $isCentralAdmin = false,
        ?int $churchId = null,
        ?string $churchRole = null,
        bool $emailVerified = true,
        ?User $actor = null,
        ?string $reason = null
    ): User {
        return DB::transaction(function () use (
            $name,
            $email,
            $password,
            $isCentralAdmin,
            $churchId,
            $churchRole,
            $emailVerified,
            $actor,
            $reason
        ) {
            $normalizedEmail = strtolower(trim($email));

            if (User::where('email', $normalizedEmail)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'A user with this email address already exists.',
                ]);
            }

            if ($churchId) {
                $churchExists = Church::where('id', $churchId)->exists();
                if (! $churchExists) {
                    throw ValidationException::withMessages([
                        'church_id' => 'The selected church does not exist.',
                    ]);
                }
            }

            $plainPassword = $password ?: Str::password(16);

            $user = User::create([
                'name' => trim($name),
                'email' => $normalizedEmail,
                'password' => Hash::make($plainPassword),
                'status' => UserStatus::Active,
                'is_central_admin' => $isCentralAdmin,
                'email_verified_at' => $emailVerified ? now() : null,
            ]);

            // Assign church membership if church_id and role specified
            if ($churchId && $churchRole) {
                $roleEnum = MembershipRole::tryFrom($churchRole);
                if ($roleEnum) {
                    ChurchMembership::create([
                        'church_id' => $churchId,
                        'user_id' => $user->id,
                        'role' => $roleEnum,
                        'status' => MembershipStatus::Active,
                        'invited_by' => $actor?->id,
                        'activated_at' => now(),
                        'reason' => $reason ?? 'Initial role assigned during user provisioning',
                    ]);
                }
            }

            $this->auditService->record(
                action: 'user.created',
                target: $user,
                churchId: $churchId,
                reason: $reason ?? 'User created by admin',
                afterData: [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status->value,
                    'is_central_admin' => $user->is_central_admin,
                    'church_id' => $churchId,
                    'church_role' => $churchRole,
                ],
                actorUserId: $actor?->id,
            );

            return $user;
        });
    }
}

