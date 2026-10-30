<?php

namespace Tests\Feature\Admin;

use App\Enums\PricingStatus;
use App\Enums\UserStatus;
use App\Models\Church;
use App\Models\ChurchBranding;
use App\Models\PricingConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CentralAdminPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $regularUser;

    protected Church $church;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'is_central_admin' => true,
            'status' => UserStatus::Active,
        ]);

        $this->regularUser = User::factory()->create([
            'is_central_admin' => false,
            'status' => UserStatus::Active,
        ]);

        $this->church = Church::factory()->create([
            'status' => 'published',
        ]);
        ChurchBranding::factory()->create(['church_id' => $this->church->id]);
    }

    public function test_non_central_admin_is_forbidden_from_payment_settings(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.payment-settings.index'));
        $response->assertForbidden();
    }

    public function test_central_admin_can_view_payment_settings(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.payment-settings.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/PaymentSettings/Index'));
    }

    public function test_central_admin_can_create_draft_pricing_and_fee_configuration(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.payment-settings.configs.store'), [
            'name' => 'Diocese 2026 Schedule',
            'currency' => 'PHP',
            'base_price_per_intention' => '150.00',
            'service_fee_type' => 'fixed_per_order',
            'service_fee_amount' => '15.00',
            'service_fee_label' => 'Diocese Maintenance Fee',
            'service_fee_active' => true,
            'change_reason' => 'Establishing standardized online intention fee.',
        ]);

        $response->assertRedirect(route('admin.payment-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pricing_configurations', [
            'name' => 'Diocese 2026 Schedule',
            'scope' => 'global',
            'status' => PricingStatus::Draft->value,
            'version' => 1,
        ]);

        $config = PricingConfiguration::where('name', 'Diocese 2026 Schedule')->first();
        $this->assertEquals('fixed_per_order', $config->pricing_rules['service_fee']['type']);
        $this->assertEquals('15.00', $config->pricing_rules['service_fee']['amount']);
        $this->assertTrue($config->pricing_rules['service_fee']['is_active']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pricing_config.created',
            'target_type' => PricingConfiguration::class,
            'target_id' => (string) $config->id,
            'actor_user_id' => $this->admin->id,
        ]);
    }

    public function test_central_admin_can_approve_draft_configuration_and_retires_previous(): void
    {
        // Existing active approved config
        $oldConfig = PricingConfiguration::create([
            'scope' => 'global',
            'name' => 'Old Config',
            'currency' => 'PHP',
            'status' => PricingStatus::Approved,
            'pricing_rules' => ['base_price_per_intention' => '100.00'],
            'version' => 1,
            'approved_at' => now()->subMonth(),
            'approved_by' => $this->admin->id,
        ]);

        $draft = PricingConfiguration::create([
            'scope' => 'global',
            'name' => 'New Config v2',
            'currency' => 'PHP',
            'status' => PricingStatus::Draft,
            'pricing_rules' => [
                'base_price_per_intention' => '150.00',
                'service_fee' => [
                    'type' => 'percentage',
                    'amount' => '2.5',
                    'label' => 'Convenience Fee',
                    'is_active' => true,
                ],
            ],
            'version' => 2,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.payment-settings.configs.approve', ['config' => $draft->id]),
            ['reason' => 'Formally approved by the finance council.']
        );

        $response->assertRedirect(route('admin.payment-settings.index'));

        // Old config should now be retired
        $this->assertEquals(PricingStatus::Retired, $oldConfig->fresh()->status);

        // New config should be approved
        $this->assertEquals(PricingStatus::Approved, $draft->fresh()->status);
        $this->assertEquals($this->admin->id, $draft->fresh()->approved_by);
        $this->assertNotNull($draft->fresh()->approved_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pricing_config.approved',
            'target_id' => (string) $draft->id,
            'actor_user_id' => $this->admin->id,
        ]);
    }

    public function test_central_admin_can_update_parish_gcash_credentials_with_qr_image(): void
    {
        $qrFile = UploadedFile::fake()->image('cathedral_qr.png', 400, 400);

        $response = $this->actingAs($this->admin)->post(
            route('admin.payment-settings.churches.gcash', ['church' => $this->church->id]),
            [
                'gcash_name' => 'Cebu Cathedral Intention Fund',
                'gcash_number' => '09171234567',
                'gcash_qr_image' => $qrFile,
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $branding = $this->church->fresh()->branding;
        $this->assertEquals('Cebu Cathedral Intention Fund', $branding->gcash_name);
        $this->assertEquals('09171234567', $branding->gcash_number);
        $this->assertNotNull($branding->gcash_qr_path);

        Storage::disk('public')->assertExists($branding->gcash_qr_path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'church.gcash_settings_updated',
            'church_id' => $this->church->id,
            'actor_user_id' => $this->admin->id,
        ]);
    }
}
