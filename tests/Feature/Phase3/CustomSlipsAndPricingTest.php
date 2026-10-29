<?php

namespace Tests\Feature\Phase3;

use App\Enums\CategoryType;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\PricingStatus;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\ChurchMembership;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\PricingConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomSlipsAndPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setupChurchWithAdmin(): array
    {
        $church = Church::factory()->create([
            'name' => 'Mother of the Redeemer Parish',
            'slug' => 'mother-of-the-redeemer',
        ]);

        $adminUser = User::factory()->create([
            'email' => 'admin@redeemerparish.ph',
            'email_verified_at' => now(),
        ]);

        ChurchMembership::create([
            'user_id' => $adminUser->id,
            'church_id' => $church->id,
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
        ]);

        $pricingConfig = PricingConfiguration::factory()->create([
            'church_id' => $church->id,
            'scope' => 'church',
            'status' => PricingStatus::Approved,
            'currency' => 'PHP',
            'pricing_rules' => [
                'base_price_per_intention' => '150.00',
            ],
            'version' => 1,
            'effective_from' => now()->subDay(),
        ]);

        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'title' => 'Sunday Holy Mass',
            'starts_at' => now()->addDays(2)->setTime(9, 0),
            'booking_cutoff_at' => now()->addDays(2)->setTime(7, 0),
        ]);

        return [$church, $adminUser, $pricingConfig, $mass];
    }

    public function test_church_admin_can_customize_souls_category_max_limit_and_price(): void
    {
        [$church, $adminUser] = $this->setupChurchWithAdmin();

        $category = ChurchCategory::create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention->value,
            'template_type' => 'souls',
            'key' => 'souls',
            'label' => 'Mass for the Souls of',
            'price' => '150.00',
            'form_config' => ['max_souls' => 10],
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminUser)
            ->patch(route('church.categories.update', ['church' => $church->id, 'category' => $category->id]), [
                'label' => 'Mass for the Souls of (Updated)',
                'template_type' => 'souls',
                'price' => '175.00',
                'form_config' => ['max_souls' => 7],
                'description' => 'Customized to 7 souls per slip',
            ]);

        $response->assertRedirect();
        $category->refresh();

        $this->assertEquals('Mass for the Souls of (Updated)', $category->label);
        $this->assertEquals('175.00', (string) $category->price);
        $this->assertEquals(7, $category->form_config['max_souls']);
    }

    public function test_church_admin_can_customize_thanksgiving_occasions(): void
    {
        [$church, $adminUser] = $this->setupChurchWithAdmin();

        $category = ChurchCategory::create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention->value,
            'template_type' => 'thanksgiving',
            'key' => 'thanksgiving',
            'label' => 'Thanksgiving Mass',
            'price' => '150.00',
            'form_config' => [
                'occasions' => ['Birthday', 'Anniversary'],
            ],
            'is_active' => true,
        ]);

        $customOccasions = [
            'Birthday Anniversary',
            'Wedding Anniversary',
            'Passing the Exam',
            'Good Harvest',
        ];

        $response = $this->actingAs($adminUser)
            ->patch(route('church.categories.update', ['church' => $church->id, 'category' => $category->id]), [
                'label' => 'Thanksgiving Mass',
                'template_type' => 'thanksgiving',
                'price' => '150.00',
                'form_config' => ['occasions' => $customOccasions],
            ]);

        $response->assertRedirect();
        $category->refresh();

        $this->assertEquals($customOccasions, $category->form_config['occasions']);
        $this->assertEquals('150.00', (string) $category->price);
    }

    public function test_order_creation_calculates_150_per_slip_and_preserves_wording(): void
    {
        [$church, , , $mass] = $this->setupChurchWithAdmin();

        $catThanksgiving = ChurchCategory::create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention->value,
            'template_type' => 'thanksgiving',
            'key' => 'thanksgiving',
            'label' => 'Thanksgiving Mass',
            'price' => '150.00',
            'is_active' => true,
        ]);

        $catSouls = ChurchCategory::create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention->value,
            'template_type' => 'souls',
            'key' => 'souls',
            'label' => 'Mass for the Souls of',
            'price' => '150.00',
            'form_config' => ['max_souls' => 10],
            'is_active' => true,
        ]);

        $submitData = [
            'mass_schedule_id' => $mass->id,
            'submitter_name' => 'Maria Clara',
            'submitter_email' => 'maria@example.com',
            'privacy_acknowledged' => true,
            'intentions' => [
                // Slip 1: Thanksgiving
                [
                    'category_id' => $catThanksgiving->id,
                    'person_or_family_name' => 'Clara Family',
                    'wording' => "Thanksgiving Mass for:\n• Birthday Anniversary: Maria Clara\nOFFERER: Clara Family",
                ],
                // Slip 2: Mass for the Souls (10 souls on 1 slip)
                [
                    'category_id' => $catSouls->id,
                    'person_or_family_name' => 'Clara Family',
                    'wording' => "MASS FOR THE SOULS OF:\n1. Crisostomo Ibarra\n2. Elias\nOFFERER: Clara Family | Contact: 0999-416-5258",
                ],
            ],
        ];

        $response = $this->post(route('orders.store'), $submitData);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'submitter_name' => 'Maria Clara',
            'submitter_email' => 'maria@example.com',
            'expected_amount' => '300.00', // 2 slips * 150 = 300
        ]);

        $order = Order::where('submitter_email', 'maria@example.com')->first();
        $this->assertCount(2, $order->intentions);
        $this->assertStringContainsString('Crisostomo Ibarra', $order->intentions[1]->wording);
        $this->assertStringContainsString('Birthday Anniversary', $order->intentions[0]->wording);
    }
}
