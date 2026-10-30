<?php

namespace Tests\Feature\Church;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Church;
use App\Models\ChurchBranding;
use App\Models\ChurchMembership;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChurchPaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $churchAdminUser;

    protected User $otherUser;

    protected Church $church;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->church = Church::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        ChurchBranding::factory()->create(['church_id' => $this->church->id]);

        $this->churchAdminUser = User::factory()->create(['status' => UserStatus::Active]);
        ChurchMembership::factory()->create([
            'user_id' => $this->churchAdminUser->id,
            'church_id' => $this->church->id,
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
        ]);

        $this->otherUser = User::factory()->create(['status' => UserStatus::Active]);
    }

    public function test_church_admin_can_view_payment_settings(): void
    {
        $response = $this->actingAs($this->churchAdminUser)->get(
            route('church.payment-settings.edit', ['church' => $this->church->id])
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Church/PaymentSettings/Edit'));
    }

    public function test_unauthorized_user_cannot_access_church_payment_settings(): void
    {
        $response = $this->actingAs($this->otherUser)->get(
            route('church.payment-settings.edit', ['church' => $this->church->id])
        );

        // 403 forbidden or 404
        $this->assertTrue(in_array($response->status(), [403, 404], true));
    }

    public function test_church_admin_can_upload_gcash_qr_and_credentials(): void
    {
        $qrFile = UploadedFile::fake()->image('church_gcash_qr.jpg', 600, 600);

        $response = $this->actingAs($this->churchAdminUser)->post(
            route('church.payment-settings.update', ['church' => $this->church->id]),
            [
                'gcash_name' => 'San Pedro Parish GCash',
                'gcash_number' => '09181234567',
                'gcash_qr_image' => $qrFile,
            ]
        );

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $branding = $this->church->fresh()->branding;
        $this->assertEquals('San Pedro Parish GCash', $branding->gcash_name);
        $this->assertEquals('09181234567', $branding->gcash_number);
        $this->assertNotNull($branding->gcash_qr_path);

        Storage::disk('public')->assertExists($branding->gcash_qr_path);
        $this->assertStringContainsString('storage/churches/qr/', $branding->gcash_qr_url);
    }

    public function test_public_payment_screen_renders_gcash_qr_and_fee_breakdown(): void
    {
        // 1. Upload QR for church
        $qrFile = UploadedFile::fake()->image('san_pedro_qr.png', 500, 500);
        $this->actingAs($this->churchAdminUser)->post(
            route('church.payment-settings.update', ['church' => $this->church->id]),
            [
                'gcash_name' => 'San Pedro Parish Offering',
                'gcash_number' => '09221234567',
                'gcash_qr_image' => $qrFile,
            ]
        );

        // 2. Set up mass schedule
        $mass = MassSchedule::factory()->create([
            'church_id' => $this->church->id,
            'booking_cutoff_at' => now()->addDays(2),
            'starts_at' => now()->addDays(3),
        ]);

        // 3. Create an order with snapshot that includes fee breakdown
        $order = Order::factory()->create([
            'church_id' => $this->church->id,
            'mass_schedule_id' => $mass->id,
            'expected_amount' => '315.00',
            'pricing_snapshot' => [
                'slips_subtotal' => '300.00',
                'service_fee' => [
                    'type' => 'fixed_per_order',
                    'rate' => '15.00',
                    'calculated_amount' => '15.00',
                    'label' => 'Platform Convenience Fee',
                ],
                'total_amount' => '315.00',
            ],
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'church_id' => $this->church->id,
            'expected_amount' => '315.00',
        ]);

        // 4. Visit payment screen as public/guest
        $response = $this->get(route('orders.payment', ['orderNumber' => $order->order_number]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Orders/Payment')
            ->where('order.expected_amount', '315.00')
            ->where('order.pricing_snapshot.service_fee.calculated_amount', '15.00')
            ->where('church.gcash_name', 'San Pedro Parish Offering')
            ->where('church.gcash_number', '09221234567')
            ->where('church.gcash_qr_url', fn ($val) => ! empty($val))
        );
    }
}
