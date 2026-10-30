<?php

namespace Tests\Feature\Performance;

use App\Actions\Orders\CreateOrderAction;
use App\Actions\Payments\VerifyPaymentAction;
use App\Enums\CategoryType;
use App\Enums\ChurchStatus;
use App\Enums\MassStatus;
use App\Enums\PaymentStatus;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PricingConfiguration;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_submitter_email_uses_composite_index(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);
        Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'submitter_email' => 'parishioner@example.com',
            'submitter_type' => 'guest',
        ]);

        $explain = DB::select("EXPLAIN SELECT * FROM orders WHERE submitter_email = 'parishioner@example.com' AND submitter_type = 'guest'");
        $keyUsed = $explain[0]->key ?? null;

        $this->assertEquals('orders_submitter_email_type_idx', $keyUsed);
    }

    public function test_payments_review_queue_uses_status_created_at_composite_index(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);
        $order = Order::factory()->create(['church_id' => $church->id, 'mass_schedule_id' => $mass->id]);
        Payment::factory()->create([
            'church_id' => $church->id,
            'order_id' => $order->id,
            'status' => PaymentStatus::PendingVerification,
        ]);

        $explain = DB::select("EXPLAIN SELECT * FROM payments WHERE status = 'pending_verification' ORDER BY created_at ASC");
        $keyUsed = $explain[0]->key ?? null;

        $this->assertEquals('payments_status_created_at_idx', $keyUsed);
    }

    public function test_reading_list_entries_uses_reading_list_position_index(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);
        $readingList = ReadingList::create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'status' => 'preparing',
        ]);

        $explain = DB::select("EXPLAIN SELECT position FROM reading_list_entries WHERE reading_list_id = {$readingList->id} ORDER BY position DESC LIMIT 1");
        $keyUsed = $explain[0]->key ?? null;

        $this->assertEquals('rle_reading_list_position_idx', $keyUsed);
    }

    public function test_mass_schedules_resource_conflict_uses_tenant_composite_index(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        MassSchedule::factory()->create([
            'church_id' => $church->id,
            'resource_key' => 'main_altar',
            'starts_at' => '2026-11-01 10:00:00',
        ]);

        $explain = DB::select("EXPLAIN SELECT * FROM mass_schedules USE INDEX (ms_church_resource_starts_idx) WHERE church_id = {$church->id} AND resource_key = 'main_altar' AND starts_at >= '2026-11-01 00:00:00'");
        $keyUsed = $explain[0]->key ?? null;

        $this->assertEquals('ms_church_resource_starts_idx', $keyUsed);
    }

    public function test_mass_schedule_reading_list_relation_works(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);

        $relation = $mass->readingList();
        $this->assertInstanceOf(HasOne::class, $relation);
    }

    public function test_create_order_batches_category_queries(): void
    {
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create([
            'church_id' => $church->id,
            'starts_at' => now()->addDays(2),
            'booking_opens_at' => now()->subDay(),
            'booking_cutoff_at' => now()->addDay(),
            'status' => MassStatus::Scheduled,
        ]);

        $cat1 = ChurchCategory::factory()->create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention,
            'is_active' => true,
        ]);
        $cat2 = ChurchCategory::factory()->create([
            'church_id' => $church->id,
            'type' => CategoryType::Intention,
            'is_active' => true,
        ]);

        PricingConfiguration::factory()->create([
            'church_id' => $church->id,
            'scope' => 'church',
            'status' => 'approved',
            'pricing_rules' => [
                'base_price_per_intention' => '100.00',
                'service_fee' => ['type' => 'none'],
            ],
        ]);

        $categoryQueriesCount = 0;
        DB::listen(function ($query) use (&$categoryQueriesCount) {
            if (str_contains($query->sql, 'church_categories')) {
                $categoryQueriesCount++;
            }
        });

        $action = app(CreateOrderAction::class);
        $result = $action->execute([
            'mass_schedule_id' => $mass->id,
            'church_id' => $church->id,
            'submitter_name' => 'Maria Santos',
            'submitter_email' => 'maria@example.com',
            'privacy_acknowledged' => true,
            'intentions' => [
                ['category_id' => $cat1->id, 'person_or_family_name' => 'Family A', 'wording' => 'Thanksgiving'],
                ['category_id' => $cat1->id, 'person_or_family_name' => 'Family B', 'wording' => 'Thanksgiving'],
                ['category_id' => $cat2->id, 'person_or_family_name' => 'Family C', 'wording' => 'Healing'],
                ['category_id' => $cat2->id, 'person_or_family_name' => 'Family D', 'wording' => 'Healing'],
            ],
        ]);

        $this->assertNotNull($result['order']);
        // With optimization, all distinct categories are fetched in 1 single WHERE id IN (...) query!
        $this->assertEquals(1, $categoryQueriesCount);
    }

    public function test_verify_payment_populates_reading_list_without_per_intention_queries(): void
    {
        $admin = User::factory()->create(['is_central_admin' => true]);
        $church = Church::factory()->create(['status' => ChurchStatus::Published]);
        $mass = MassSchedule::factory()->create(['church_id' => $church->id]);
        $order = Order::factory()->create([
            'church_id' => $church->id,
            'mass_schedule_id' => $mass->id,
            'expected_amount' => '400.00',
        ]);

        $cat = ChurchCategory::factory()->create(['church_id' => $church->id, 'type' => CategoryType::Intention]);
        for ($i = 1; $i <= 5; $i++) {
            $order->intentions()->create([
                'church_id' => $church->id,
                'mass_schedule_id' => $mass->id,
                'category_id' => $cat->id,
                'person_or_family_name' => "Person {$i}",
                'wording' => 'Prayer',
                'sort_order' => $i,
            ]);
        }

        $payment = Payment::factory()->create([
            'church_id' => $church->id,
            'order_id' => $order->id,
            'expected_amount' => '400.00',
            'submitted_amount' => '400.00',
            'status' => PaymentStatus::PendingVerification,
        ]);

        $readingListSelectQueries = 0;
        $trackQueries = true;
        DB::listen(function ($query) use (&$readingListSelectQueries, &$trackQueries) {
            if ($trackQueries && str_contains($query->sql, 'reading_list_entries') && str_starts_with(trim(strtolower($query->sql)), 'select')) {
                $readingListSelectQueries++;
            }
        });

        $action = app(VerifyPaymentAction::class);
        $action->execute($payment, $admin, ['verified_amount' => '400.00']);

        $trackQueries = false;

        // Check entries were created
        $this->assertEquals(5, ReadingListEntry::where('church_id', $church->id)->count());
        // Previously this would execute 10 SELECT queries (2 per intention).
        // With optimization: 1 query for existing intention IDs, 1 query for max position = 2 SELECT queries!
        $this->assertEquals(2, $readingListSelectQueries);
    }
}
