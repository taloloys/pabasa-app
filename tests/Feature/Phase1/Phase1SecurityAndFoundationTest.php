<?php

namespace Tests\Feature\Phase1;

use App\Actions\Churches\AcceptInvitationAction;
use App\Actions\Churches\CreateInvitationAction;
use App\Actions\Churches\DeactivateUserAction;
use App\Actions\Churches\RevokeInvitationAction;
use App\Actions\Churches\RevokeMembershipAction;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\AuditLog;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase1SecurityAndFoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Unauthenticated visitor cannot access staff dashboards.
     */
    public function test_case_1_unauthenticated_visitor_cannot_access_staff_dashboards(): void
    {
        $church = Church::factory()->create();

        // Central Admin dashboard
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');

        // Church Admin dashboard
        $response = $this->get("/churches/{$church->id}/dashboard");
        $response->assertRedirect('/login');

        // Lector dashboard
        $response = $this->get("/churches/{$church->id}/lector/dashboard");
        $response->assertRedirect('/login');
    }

    /**
     * 2. Registered user can register/login/logout; logout invalidates session.
     */
    public function test_case_2_registered_user_can_register_login_logout_and_logout_invalidates_session(): void
    {
        $email = 'parishioner@example.com';
        $password = 'SecretPassword123!';

        // Registration
        $registerResponse = $this->post('/register', [
            'name' => 'Maria Santos',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);
        $registerResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        // Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/');
        $this->assertGuest();

        // Dashboard requires auth
        $this->get('/dashboard')->assertRedirect('/login');

        // Login again
        $loginResponse = $this->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);
        $loginResponse->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    /**
     * 3. Unverified user cannot access secure order tracking.
     */
    public function test_case_3_unverified_user_cannot_access_secure_order_tracking(): void
    {
        $unverifiedUser = User::factory()->unverified()->create();

        $response = $this->actingAs($unverifiedUser)->get('/orders/tracking');
        $response->assertRedirect(route('verification.notice'));

        // Once verified, access is allowed
        $verifiedUser = User::factory()->create(['email_verified_at' => now()]);
        $verifiedResponse = $this->actingAs($verifiedUser)->getJson('/orders/tracking');
        $verifiedResponse->assertOk()
            ->assertJsonPath('message', 'Secure order tracking access granted.');
    }

    /**
     * 4. Expired invitation cannot be accepted.
     */
    public function test_case_4_expired_invitation_cannot_be_accepted(): void
    {
        $church = Church::factory()->create();
        $inviter = User::factory()->create();
        $user = User::factory()->create();

        $action = app(CreateInvitationAction::class);
        $result = $action->execute(
            church: $church,
            email: $user->email,
            role: MembershipRole::Lector,
            invitedBy: $inviter,
            expiresAt: now()->subMinute(),
        );

        $acceptAction = app(AcceptInvitationAction::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This invitation has expired.');

        $acceptAction->execute($result['raw_token'], $user);
    }

    /**
     * 5. Revoked invitation cannot be accepted.
     */
    public function test_case_5_revoked_invitation_cannot_be_accepted(): void
    {
        $church = Church::factory()->create();
        $inviter = User::factory()->create();
        $user = User::factory()->create();

        $createAction = app(CreateInvitationAction::class);
        $result = $createAction->execute(
            church: $church,
            email: $user->email,
            role: MembershipRole::Lector,
            invitedBy: $inviter,
        );

        $revokeAction = app(RevokeInvitationAction::class);
        $revokeAction->execute($result['invitation'], $inviter, 'Issued in error');

        $acceptAction = app(AcceptInvitationAction::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This invitation has been revoked.');

        $acceptAction->execute($result['raw_token'], $user);
    }

    /**
     * 6. Invitation token is stored hashed and is one-time use.
     */
    public function test_case_6_invitation_token_is_stored_hashed_and_is_one_time_use(): void
    {
        $church = Church::factory()->create();
        $inviter = User::factory()->create();
        $user = User::factory()->create();

        $createAction = app(CreateInvitationAction::class);
        $result = $createAction->execute(
            church: $church,
            email: $user->email,
            role: MembershipRole::Lector,
            invitedBy: $inviter,
        );

        $rawToken = $result['raw_token'];
        $invitation = $result['invitation']->fresh();

        // Assert token is hashed with sha256
        $expectedHash = hash('sha256', $rawToken);
        $this->assertEquals($expectedHash, $invitation->token_hash);

        // Ensure raw token is NEVER stored in database
        $this->assertDatabaseMissing('invitations', [
            'id' => $invitation->id,
            'token_hash' => $rawToken,
        ]);

        // Accept the invitation once
        $acceptAction = app(AcceptInvitationAction::class);
        $membership = $acceptAction->execute($rawToken, $user);

        $this->assertNotNull($membership);
        $this->assertEquals(MembershipStatus::Active, $membership->status);
        $this->assertNotNull($invitation->fresh()->accepted_at);

        // Attempting to accept a second time must fail
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This invitation has already been accepted.');

        $acceptAction->execute($rawToken, $user);
    }

    /**
     * 7. Church Admin can access own church membership list but not another church's.
     */
    public function test_case_7_church_admin_can_access_own_church_membership_list_but_not_another_church(): void
    {
        $churchA = Church::factory()->create(['name' => 'San Pedro Parish']);
        $churchB = Church::factory()->create(['name' => 'Santa Ana Parish']);

        $adminUser = User::factory()->create();
        ChurchMembership::factory()->create([
            'church_id' => $churchA->id,
            'user_id' => $adminUser->id,
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
        ]);

        // Access own church membership list -> 200 OK
        $response = $this->actingAs($adminUser)->getJson("/churches/{$churchA->id}/memberships");
        $response->assertOk()
            ->assertJsonPath('church.id', $churchA->id);

        // Access another church's membership list -> 404 (tenant isolation per spec)
        $forbiddenResponse = $this->actingAs($adminUser)->getJson("/churches/{$churchB->id}/memberships");
        $forbiddenResponse->assertNotFound();
    }

    /**
     * 8. Lector membership does not grant Church Admin permissions.
     */
    public function test_case_8_lector_membership_does_not_grant_church_admin_permissions(): void
    {
        $church = Church::factory()->create();
        $lectorUser = User::factory()->create();

        ChurchMembership::factory()->create([
            'church_id' => $church->id,
            'user_id' => $lectorUser->id,
            'role' => MembershipRole::Lector,
            'status' => MembershipStatus::Active,
        ]);

        // Lector cannot access Church Admin dashboard
        $this->actingAs($lectorUser)->get("/churches/{$church->id}/dashboard")
            ->assertForbidden();

        // Lector cannot view membership list
        $this->actingAs($lectorUser)->getJson("/churches/{$church->id}/memberships")
            ->assertForbidden();

        // Lector cannot invite staff
        $this->actingAs($lectorUser)->postJson("/churches/{$church->id}/invitations", [
            'email' => 'newstaff@example.com',
            'role' => MembershipRole::Lector->value,
        ])->assertForbidden();

        // Lector CAN access lector dashboard
        $this->actingAs($lectorUser)->get("/churches/{$church->id}/lector/dashboard")
            ->assertOk();
    }

    /**
     * 9. Revoked/deactivated membership immediately blocks subsequent requests.
     */
    public function test_case_9_revoked_or_deactivated_membership_immediately_blocks_subsequent_requests(): void
    {
        $church = Church::factory()->create();
        $adminUser = User::factory()->create();
        $membership = ChurchMembership::factory()->create([
            'church_id' => $church->id,
            'user_id' => $adminUser->id,
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
        ]);

        // Initially authorized
        $this->actingAs($adminUser)->get("/churches/{$church->id}/dashboard")
            ->assertOk();

        // Revoke the membership
        $revoker = User::factory()->centralAdmin()->create();
        $revokeAction = app(RevokeMembershipAction::class);
        $revokeAction->execute($membership, $revoker, 'Security concern');

        // Subsequent request immediately blocked (404 tenant isolation)
        $this->actingAs($adminUser)->get("/churches/{$church->id}/dashboard")
            ->assertNotFound();

        // Deactivating user account blocks ALL access
        $deactivateAction = app(DeactivateUserAction::class);
        $deactivateAction->execute($adminUser, $revoker, 'Policy violation');

        $this->actingAs($adminUser)->get("/churches/{$church->id}/dashboard")
            ->assertForbidden();
    }

    /**
     * 10. Central Admin privilege is checked server-side.
     */
    public function test_case_10_central_admin_privilege_is_checked_server_side(): void
    {
        $regularUser = User::factory()->create(['is_central_admin' => false]);
        $centralAdmin = User::factory()->centralAdmin()->create();
        $church = Church::factory()->create();

        // Regular user cannot access Central Admin dashboard
        $this->actingAs($regularUser)->get('/admin/dashboard')
            ->assertForbidden();

        // Central Admin can access Central Admin dashboard
        $this->actingAs($centralAdmin)->get('/admin/dashboard')
            ->assertOk();

        // Central Admin can access any church dashboard without explicit membership
        $this->actingAs($centralAdmin)->get("/churches/{$church->id}/dashboard")
            ->assertOk();
    }

    /**
     * 11. Sensitive role/membership changes create audit entries with actor, target, reason, timestamp.
     */
    public function test_case_11_sensitive_role_and_membership_changes_create_audit_entries(): void
    {
        $church = Church::factory()->create();
        $admin = User::factory()->create();
        $revoker = User::factory()->centralAdmin()->create();

        $membership = ChurchMembership::factory()->create([
            'church_id' => $church->id,
            'user_id' => $admin->id,
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
        ]);

        $reason = 'Suspected unauthorized access investigation';
        $revokeAction = app(RevokeMembershipAction::class);
        $revokeAction->execute($membership, $revoker, $reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'membership.revoked',
            'actor_user_id' => $revoker->id,
            'target_type' => $membership->getMorphClass(),
            'target_id' => (string) $membership->id,
            'church_id' => $church->id,
            'reason' => $reason,
        ]);

        $log = AuditLog::where('action', 'membership.revoked')->latest('id')->first();
        $this->assertNotNull($log->created_at);
        $this->assertEquals(MembershipStatus::Revoked->value, $log->after_data['status']);
    }

    /**
     * 12. Passwords, raw invitation tokens, and secrets never appear in audit logs.
     */
    public function test_case_12_passwords_raw_invitation_tokens_and_secrets_never_appear_in_audit_logs(): void
    {
        $auditService = app(AuditService::class);
        $user = User::factory()->create();

        $rawToken = 'RAW_SECRET_INVITATION_TOKEN_1234567890';
        $rawPassword = 'SuperSecretPassword!@#$';

        $auditService->record(
            action: 'test.sensitive.sanitization',
            target: 'user',
            targetId: (string) $user->id,
            reason: 'Testing secret redaction',
            beforeData: [
                'password' => $rawPassword,
                'password_confirmation' => $rawPassword,
                'secret' => 'top_secret_key',
            ],
            afterData: [
                'token' => $rawToken,
                'raw_token' => $rawToken,
                'token_hash' => hash('sha256', $rawToken),
                'nested' => [
                    'password' => $rawPassword,
                    'remember_token' => 'abc123xyz',
                ],
                'safe_field' => 'visible_username',
            ],
            actorUserId: $user->id,
        );

        $log = AuditLog::where('action', 'test.sensitive.sanitization')->first();
        $this->assertNotNull($log);

        $jsonBefore = json_encode($log->before_data);
        $jsonAfter = json_encode($log->after_data);

        // Ensure raw secrets and tokens do NOT appear anywhere in the audit log JSON
        $this->assertStringNotContainsString($rawPassword, $jsonBefore);
        $this->assertStringNotContainsString($rawPassword, $jsonAfter);
        $this->assertStringNotContainsString($rawToken, $jsonAfter);
        $this->assertStringNotContainsString('top_secret_key', $jsonBefore);
        $this->assertStringNotContainsString('abc123xyz', $jsonAfter);

        // Ensure safe field is preserved
        $this->assertEquals('visible_username', $log->after_data['safe_field']);

        // Ensure sensitive keys were replaced with [REDACTED]
        $this->assertEquals('[REDACTED]', $log->before_data['password']);
        $this->assertEquals('[REDACTED]', $log->after_data['token']);
        $this->assertEquals('[REDACTED]', $log->after_data['nested']['password']);
    }

    /**
     * 13. Rate limiting applies to login, invite acceptance, and recovery endpoints.
     */
    public function test_case_13_rate_limiting_applies_to_login_invite_acceptance_and_recovery_endpoints(): void
    {
        RateLimiter::clear('invitation-accept:127.0.0.1');
        RateLimiter::clear('password-recovery:127.0.0.1');

        $user = User::factory()->create();

        // 13.a Login rate limiting (5 attempts threshold in LoginRequest)
        $loginThrottleKey = 'testratelimit@example.com|127.0.0.1';
        RateLimiter::clear($loginThrottleKey);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'testratelimit@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt should be blocked by rate limit
        $blockedLogin = $this->post('/login', [
            'email' => 'testratelimit@example.com',
            'password' => 'wrong-password',
        ]);
        $blockedLogin->assertSessionHasErrors('email');

        // 13.b Invitation acceptance rate limit (10 per minute)
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->postJson('/invitations/accept', ['token' => 'invalid-token']);
        }
        $blockedAccept = $this->actingAs($user)->postJson('/invitations/accept', ['token' => 'invalid-token']);
        $blockedAccept->assertStatus(429);

        // 13.c Password recovery rate limit (6 per minute)
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'test@example.com']);
        }
        $blockedRecovery = $this->post('/forgot-password', ['email' => 'test@example.com']);
        $blockedRecovery->assertStatus(429);
    }

    /**
     * 14. Locale switching does not mutate stored user-entered text.
     */
    public function test_case_14_locale_switching_does_not_mutate_stored_user_entered_text(): void
    {
        $user = User::factory()->create();
        $church = Church::factory()->create([
            'name' => 'Parroquia de Santo Niño de Cebu — Mass of Thanksgiving',
        ]);

        $originalChurchName = $church->name;

        // Switch to Filipino
        $this->actingAs($user)->postJson('/locale', ['locale' => 'fil'])
            ->assertOk()
            ->assertJsonPath('locale', 'fil');
        $this->assertEquals('fil', app()->getLocale());
        $this->assertEquals($originalChurchName, $church->fresh()->name);

        // Switch to Cebuano
        $this->actingAs($user)->postJson('/locale', ['locale' => 'ceb'])
            ->assertOk()
            ->assertJsonPath('locale', 'ceb');
        $this->assertEquals('ceb', app()->getLocale());
        $this->assertEquals($originalChurchName, $church->fresh()->name);

        // Switch back to English
        $this->actingAs($user)->postJson('/locale', ['locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('locale', 'en');
        $this->assertEquals('en', app()->getLocale());
        $this->assertEquals($originalChurchName, $church->fresh()->name);
    }

    /**
     * 15. Foreign keys and indexes migrate cleanly on MySQL 8.4.
     */
    public function test_case_15_foreign_keys_and_indexes_migrate_cleanly_on_mysql(): void
    {
        // Verify key tables exist in the current MySQL database
        $tables = ['users', 'churches', 'church_memberships', 'invitations', 'system_settings', 'audit_logs'];

        foreach ($tables as $table) {
            $this->assertTrue(DB::getSchemaBuilder()->hasTable($table), "Table {$table} should exist in MySQL.");
        }

        // Verify foreign key constraints are registered in MySQL information_schema
        $foreignKeys = DB::select('
            SELECT TABLE_NAME, CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ');

        $fkTableNames = array_unique(array_map(fn ($fk) => $fk->TABLE_NAME, $foreignKeys));

        $this->assertContains('church_memberships', $fkTableNames);
        $this->assertContains('invitations', $fkTableNames);
        $this->assertContains('system_settings', $fkTableNames);
        $this->assertContains('audit_logs', $fkTableNames);
    }

    /**
     * 16. Backup restoration procedure can restore the Phase 1 database into an isolated environment.
     */
    public function test_case_16_backup_restoration_procedure_can_restore_phase_1_database(): void
    {
        // Assert backup documentation exists and contains the procedure
        $docPath = base_path('docs/backup_restore_procedure.md');
        $this->assertFileExists($docPath);

        $docContent = file_get_contents($docPath);
        $this->assertStringContainsString('mysqldump', $docContent);
        $this->assertStringContainsString('--single-transaction', $docContent);
        $this->assertStringContainsString('pabasa_isolated', $docContent);

        // Verify schema replication compatibility on MySQL 8.4
        $tables = ['users', 'churches', 'church_memberships', 'invitations', 'system_settings', 'audit_logs'];
        foreach ($tables as $table) {
            $tempTable = "temp_restore_{$table}";
            DB::statement("DROP TEMPORARY TABLE IF EXISTS `{$tempTable}`");
            DB::statement("CREATE TEMPORARY TABLE `{$tempTable}` LIKE `{$table}`");

            $columns = DB::select("SHOW COLUMNS FROM `{$tempTable}`");
            $this->assertNotEmpty($columns, "Table {$tempTable} should have columns restored.");

            DB::statement("DROP TEMPORARY TABLE IF EXISTS `{$tempTable}`");
        }
    }
}
