<?php

namespace Tests\Feature\Admin;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function createCentralAdmin(): User
    {
        return User::factory()->create([
            'is_central_admin' => true,
            'status' => UserStatus::Active,
        ]);
    }

    protected function createChurchStaff(Church $church, MembershipRole $role): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        ChurchMembership::factory()->create([
            'church_id' => $church->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => MembershipStatus::Active,
        ]);

        return $user;
    }

    public function test_central_admin_can_view_user_directory_with_search_and_filters(): void
    {
        $admin = $this->createCentralAdmin();
        $church = Church::factory()->create(['name' => 'San Pedro Calungsod Parish']);

        $staffUser = User::factory()->create(['name' => 'Teresa Magbanua', 'email' => 'teresa@example.com']);
        ChurchMembership::factory()->create([
            'church_id' => $church->id,
            'user_id' => $staffUser->id,
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
        ]);

        $plainUser = User::factory()->create(['name' => 'Pedro Penduko', 'email' => 'pedro@example.com']);

        // Test search
        $response = $this->actingAs($admin)->getJson('/admin/users?search=Teresa');
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Teresa Magbanua']);
        $response->assertJsonMissing(['name' => 'Pedro Penduko']);

        // Test role filter
        $responseRole = $this->actingAs($admin)->getJson('/admin/users?role=church_admin');
        $responseRole->assertOk();
        $responseRole->assertJsonFragment(['name' => 'Teresa Magbanua']);

        // Test parish filter
        $responseChurch = $this->actingAs($admin)->getJson('/admin/users?church_id='.$church->id);
        $responseChurch->assertOk();
        $responseChurch->assertJsonFragment(['name' => 'Teresa Magbanua']);
    }

    public function test_central_admin_can_create_user_with_direct_password_and_initial_church_role(): void
    {
        $admin = $this->createCentralAdmin();
        $church = Church::factory()->create(['name' => 'Nuestra Senora de Gracia']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Fr. Jose Burgos',
            'email' => 'jose.burgos@gracia.ph',
            'password' => 'SanctuaryPassword2026!',
            'password_confirmation' => 'SanctuaryPassword2026!',
            'is_central_admin' => false,
            'church_id' => $church->id,
            'church_role' => 'church_admin',
            'email_verified' => true,
            'reason' => 'Appointed parish administrator',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $createdUser = User::where('email', 'jose.burgos@gracia.ph')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('Fr. Jose Burgos', $createdUser->name);
        $this->assertTrue(Hash::check('SanctuaryPassword2026!', $createdUser->password));
        $this->assertNotNull($createdUser->email_verified_at);

        // Verify church membership was created
        $this->assertTrue($createdUser->hasRoleInChurch($church, MembershipRole::ChurchAdmin));

        // Verify audit log exists and contains no passwords
        $audit = AuditLog::where('target_id', (string) $createdUser->id)
            ->where('action', 'user.created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertArrayNotHasKey('password', $audit->after_data ?? []);
    }

    public function test_central_admin_can_toggle_central_admin_status(): void
    {
        $admin = $this->createCentralAdmin();
        $targetUser = User::factory()->create(['is_central_admin' => false]);

        // Grant Central Admin
        $responseGrant = $this->actingAs($admin)->post("/admin/users/{$targetUser->id}/roles", [
            'role_type' => 'central_admin',
            'is_central_admin' => true,
            'reason' => 'Promoted to archdiocese chancery staff',
        ]);

        $responseGrant->assertRedirect();
        $this->assertTrue($targetUser->fresh()->isCentralAdmin());

        // Revoke Central Admin
        $responseRevoke = $this->actingAs($admin)->post("/admin/users/{$targetUser->id}/roles", [
            'role_type' => 'central_admin',
            'is_central_admin' => false,
            'reason' => 'Stepped down from chancery',
        ]);

        $responseRevoke->assertRedirect();
        $this->assertFalse($targetUser->fresh()->isCentralAdmin());
    }

    public function test_central_admin_cannot_demote_themselves_if_last_active_admin(): void
    {
        $admin = $this->createCentralAdmin();

        // Ensure $admin is the sole active central admin
        User::where('id', '!=', $admin->id)->update(['is_central_admin' => false]);

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/roles", [
            'role_type' => 'central_admin',
            'is_central_admin' => false,
        ]);

        $response->assertSessionHasErrors('is_central_admin');
        $this->assertTrue($admin->fresh()->isCentralAdmin());
    }

    public function test_central_admin_cannot_deactivate_themselves_if_last_active_admin(): void
    {
        $admin = $this->createCentralAdmin();
        User::where('id', '!=', $admin->id)->update(['is_central_admin' => false]);

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/toggle-status", [
            'status' => 'disabled',
            'reason' => 'Accidental self deactivation',
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertEquals(UserStatus::Active, $admin->fresh()->status);
    }

    public function test_central_admin_can_assign_church_membership_with_duplicate_role_reactivation(): void
    {
        $admin = $this->createCentralAdmin();
        $church = Church::factory()->create();
        $user = User::factory()->create();

        // Assign lector role
        $response = $this->actingAs($admin)->post("/admin/users/{$user->id}/roles", [
            'role_type' => 'church_membership',
            'church_id' => $church->id,
            'role' => 'lector',
            'reason' => 'Assigned lector',
        ]);

        $response->assertRedirect();
        $membership = ChurchMembership::where('church_id', $church->id)
            ->where('user_id', $user->id)
            ->where('role', MembershipRole::Lector)
            ->first();

        $this->assertNotNull($membership);
        $this->assertEquals(MembershipStatus::Active, $membership->status);

        // Revoke the role
        $membership->update([
            'status' => MembershipStatus::Revoked,
            'revoked_at' => now(),
        ]);

        // Reassign the same role -> should reactivate without duplicate key collision
        $reassignResponse = $this->actingAs($admin)->post("/admin/users/{$user->id}/roles", [
            'role_type' => 'church_membership',
            'church_id' => $church->id,
            'role' => 'lector',
            'reason' => 'Reinstated as active lector',
        ]);

        $reassignResponse->assertRedirect();
        $this->assertEquals(MembershipStatus::Active, $membership->fresh()->status);
        $this->assertNull($membership->fresh()->revoked_at);
    }

    public function test_central_admin_can_revoke_church_membership(): void
    {
        $admin = $this->createCentralAdmin();
        $church = Church::factory()->create();
        $user = User::factory()->create();

        $membership = ChurchMembership::factory()->create([
            'church_id' => $church->id,
            'user_id' => $user->id,
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/users/{$user->id}/roles/{$membership->id}", [
            'reason' => 'Term of office concluded',
        ]);

        $response->assertRedirect();
        $this->assertEquals(MembershipStatus::Revoked, $membership->fresh()->status);
    }

    public function test_non_central_admin_is_forbidden_from_user_management(): void
    {
        $church = Church::factory()->create();
        $churchAdmin = $this->createChurchStaff($church, MembershipRole::ChurchAdmin);
        $lector = $this->createChurchStaff($church, MembershipRole::Lector);
        $parishioner = User::factory()->create();

        // Church admin cannot access admin user management
        $this->actingAs($churchAdmin)->get('/admin/users')->assertForbidden();
        $this->actingAs($churchAdmin)->post('/admin/users', [
            'name' => 'Illegal User',
            'email' => 'illegal@example.com',
        ])->assertForbidden();

        // Lector cannot access
        $this->actingAs($lector)->get('/admin/users')->assertForbidden();

        // Parishioner cannot access
        $this->actingAs($parishioner)->get('/admin/users')->assertForbidden();
    }

    public function test_deactivating_user_disables_access_and_records_audit(): void
    {
        $admin = $this->createCentralAdmin();
        $targetUser = User::factory()->create(['status' => UserStatus::Active]);

        $response = $this->actingAs($admin)->post("/admin/users/{$targetUser->id}/toggle-status", [
            'status' => 'disabled',
            'reason' => 'Account suspended pending investigation',
        ]);

        $response->assertRedirect();
        $this->assertEquals(UserStatus::Disabled, $targetUser->fresh()->status);

        // Audit log exists
        $audit = AuditLog::where('target_id', (string) $targetUser->id)
            ->where('action', 'user.deactivated')
            ->first();

        $this->assertNotNull($audit);

        // Activating back
        $activateResponse = $this->actingAs($admin)->post("/admin/users/{$targetUser->id}/toggle-status", [
            'status' => 'active',
            'reason' => 'Account reinstated',
        ]);

        $activateResponse->assertRedirect();
        $this->assertEquals(UserStatus::Active, $targetUser->fresh()->status);
    }
}
