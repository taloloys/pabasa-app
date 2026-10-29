<?php

namespace Tests\Feature\Admin;

use App\Enums\CategoryType;
use App\Enums\ChurchStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Church;
use App\Models\ChurchBranding;
use App\Models\ChurchCategory;
use App\Models\ChurchMembership;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function createCentralAdmin(): User
    {
        return User::factory()->create(['is_central_admin' => true]);
    }

    protected function createChurchStaff(Church $church, MembershipRole $role): User
    {
        $user = User::factory()->create();
        ChurchMembership::factory()->create([
            'church_id' => $church->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => MembershipStatus::Active,
        ]);

        return $user;
    }

    public function test_central_admin_can_register_new_parish_with_branding_and_default_categories(): void
    {
        $admin = $this->createCentralAdmin();

        $response = $this->actingAs($admin)->post('/admin/churches', [
            'name' => 'St. Joseph the Worker Parish',
            'city' => 'Mandaue City',
            'province' => 'Cebu',
            'address' => 'Plaridel St, Alang-alang',
            'postal_code' => '6014',
            'contact_email' => 'stjoseph.mandaue@example.com',
            'contact_phone' => '+63 32 345 6789',
            'timezone' => 'Asia/Manila',
            'auto_verify' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $church = Church::where('name', 'St. Joseph the Worker Parish')->first();
        $this->assertNotNull($church);
        $this->assertEquals(ChurchStatus::Draft, $church->status);
        $this->assertEquals('st-joseph-the-worker-parish', $church->slug);
        $this->assertEquals('Mandaue City', $church->city);
        $this->assertEquals('Cebu', $church->province);
        $this->assertEquals('Asia/Manila', $church->timezone);

        // Verify branding created
        $branding = ChurchBranding::where('church_id', $church->id)->first();
        $this->assertNotNull($branding);
        $this->assertEquals('stjoseph.mandaue@example.com', $branding->contact_email);

        // Verify default intention categories created
        $categories = ChurchCategory::where('church_id', $church->id)
            ->where('type', CategoryType::Intention->value)
            ->get();

        $this->assertCount(4, $categories);
        $this->assertTrue($categories->contains('key', 'thanksgiving'));
        $this->assertTrue($categories->contains('key', 'souls'));
        $this->assertTrue($categories->contains('key', 'healing'));
        $this->assertTrue($categories->contains('key', 'petition'));

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'church.created',
            'target_type' => Church::class,
            'target_id' => (string) $church->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_central_admin_can_register_new_parish_with_auto_verify(): void
    {
        $admin = $this->createCentralAdmin();

        $response = $this->actingAs($admin)->post('/admin/churches', [
            'name' => 'Archdiocesan Shrine of Our Lady of Lourdes',
            'city' => 'Cebu City',
            'province' => 'Cebu',
            'timezone' => 'Asia/Manila',
            'auto_verify' => true,
        ]);

        $response->assertRedirect();

        $church = Church::where('name', 'Archdiocesan Shrine of Our Lady of Lourdes')->first();
        $this->assertNotNull($church);
        $this->assertEquals(ChurchStatus::Verified, $church->status);
        $this->assertNotNull($church->verified_at);
        $this->assertEquals($admin->id, $church->verified_by);
    }

    public function test_central_admin_can_invite_initial_parish_administrator_during_registration(): void
    {
        $admin = $this->createCentralAdmin();

        $response = $this->actingAs($admin)->post('/admin/churches', [
            'name' => 'Holy Family Parish',
            'city' => 'Lapu-Lapu City',
            'province' => 'Cebu',
            'timezone' => 'Asia/Manila',
            'initial_admin_email' => 'pastor.lapulapu@example.com',
        ]);

        $response->assertRedirect();

        $church = Church::where('name', 'Holy Family Parish')->first();
        $this->assertNotNull($church);

        // Verify staff invitation created
        $invitation = Invitation::where('church_id', $church->id)
            ->where('email', 'pastor.lapulapu@example.com')
            ->first();

        $this->assertNotNull($invitation);
        $this->assertEquals(MembershipRole::ChurchAdmin, $invitation->role);
        $this->assertEquals($admin->id, $invitation->invited_by);
    }

    public function test_auto_slug_generator_avoids_collisions(): void
    {
        $admin = $this->createCentralAdmin();

        $this->actingAs($admin)->post('/admin/churches', [
            'name' => 'San Roque Parish',
            'city' => 'Mandaue City',
            'province' => 'Cebu',
            'timezone' => 'Asia/Manila',
        ]);

        $this->actingAs($admin)->post('/admin/churches', [
            'name' => 'San Roque Parish',
            'city' => 'Mandaue City',
            'province' => 'Cebu',
            'timezone' => 'Asia/Manila',
        ]);

        $churches = Church::where('name', 'San Roque Parish')->get();
        $this->assertCount(2, $churches);
        $this->assertNotEquals($churches[0]->slug, $churches[1]->slug);
    }

    public function test_non_central_admin_cannot_register_parish(): void
    {
        $existingChurch = Church::factory()->create();
        $churchAdmin = $this->createChurchStaff($existingChurch, MembershipRole::ChurchAdmin);
        $lector = $this->createChurchStaff($existingChurch, MembershipRole::Lector);
        $regularUser = User::factory()->create();

        // Church admin forbidden
        $this->actingAs($churchAdmin)->post('/admin/churches', [
            'name' => 'Unauthorized Parish 1',
            'city' => 'City',
            'province' => 'Province',
            'timezone' => 'Asia/Manila',
        ])->assertForbidden();

        // Lector forbidden
        $this->actingAs($lector)->post('/admin/churches', [
            'name' => 'Unauthorized Parish 2',
            'city' => 'City',
            'province' => 'Province',
            'timezone' => 'Asia/Manila',
        ])->assertForbidden();

        // Regular user forbidden
        $this->actingAs($regularUser)->post('/admin/churches', [
            'name' => 'Unauthorized Parish 3',
            'city' => 'City',
            'province' => 'Province',
            'timezone' => 'Asia/Manila',
        ])->assertForbidden();

        // Guest receives 403 Forbidden
        $this->post('/admin/churches', [
            'name' => 'Unauthorized Parish 4',
            'city' => 'City',
            'province' => 'Province',
            'timezone' => 'Asia/Manila',
        ])->assertForbidden();

        $this->assertEquals(1, Church::count());
    }

    public function test_validation_rules_reject_invalid_data(): void
    {
        $admin = $this->createCentralAdmin();

        $response = $this->actingAs($admin)->postJson('/admin/churches', [
            'name' => '',
            'city' => '',
            'province' => '',
            'contact_email' => 'not-an-email',
            'timezone' => 'Invalid/Timezone',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'city', 'province', 'contact_email', 'timezone']);
    }
}
