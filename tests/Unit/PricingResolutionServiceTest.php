<?php

namespace Tests\Unit;

use App\Enums\PricingStatus;
use App\Models\Church;
use App\Models\PricingConfiguration;
use App\Services\Pricing\PricingResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PricingResolutionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PricingResolutionService $service;

    protected Church $church;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PricingResolutionService::class);
        $this->church = Church::factory()->create();
    }

    public function test_throws_validation_exception_when_no_approved_pricing_config_exists(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->resolve($this->church, 1);
    }

    public function test_calculates_without_fee_when_fee_is_none_or_inactive(): void
    {
        PricingConfiguration::create([
            'scope' => 'global',
            'name' => 'Base Pricing',
            'currency' => 'PHP',
            'status' => PricingStatus::Approved,
            'version' => 1,
            'pricing_rules' => [
                'base_price_per_intention' => '150.00',
                'service_fee' => [
                    'type' => 'none',
                    'amount' => '0.00',
                    'is_active' => false,
                ],
            ],
        ]);

        $result = $this->service->resolve($this->church, 2);

        $this->assertEquals('300.00', $result['slips_subtotal']);
        $this->assertEquals('0.00', $result['service_fee_amount']);
        $this->assertEquals('300.00', $result['total_amount']);
    }

    public function test_calculates_fixed_per_order_fee(): void
    {
        PricingConfiguration::create([
            'scope' => 'global',
            'name' => 'Fixed Fee Pricing',
            'currency' => 'PHP',
            'status' => PricingStatus::Approved,
            'version' => 1,
            'pricing_rules' => [
                'base_price_per_intention' => '150.00',
                'service_fee' => [
                    'type' => 'fixed_per_order',
                    'amount' => '15.00',
                    'label' => 'Convenience Fee',
                    'is_active' => true,
                ],
            ],
        ]);

        // 3 intentions = 3 * 150 = 450 subtotal + 15 flat fee = 465.00
        $result = $this->service->resolve($this->church, 3);

        $this->assertEquals('450.00', $result['slips_subtotal']);
        $this->assertEquals('15.00', $result['service_fee_amount']);
        $this->assertEquals('fixed_per_order', $result['service_fee_type']);
        $this->assertEquals('465.00', $result['total_amount']);
    }

    public function test_calculates_fixed_per_slip_fee(): void
    {
        PricingConfiguration::create([
            'scope' => 'global',
            'name' => 'Per-Slip Fee Pricing',
            'currency' => 'PHP',
            'status' => PricingStatus::Approved,
            'version' => 1,
            'pricing_rules' => [
                'base_price_per_intention' => '100.00',
                'service_fee' => [
                    'type' => 'fixed_per_slip',
                    'amount' => '5.00',
                    'label' => 'Per Slip Processing Fee',
                    'is_active' => true,
                ],
            ],
        ]);

        // 4 intentions = 4 * 100 = 400 subtotal + (4 * 5) fee = 420.00
        $result = $this->service->resolve($this->church, 4);

        $this->assertEquals('400.00', $result['slips_subtotal']);
        $this->assertEquals('20.00', $result['service_fee_amount']);
        $this->assertEquals('fixed_per_slip', $result['service_fee_type']);
        $this->assertEquals('420.00', $result['total_amount']);
    }

    public function test_calculates_percentage_fee_with_decimal_safety(): void
    {
        PricingConfiguration::create([
            'scope' => 'global',
            'name' => 'Percentage Fee Pricing',
            'currency' => 'PHP',
            'status' => PricingStatus::Approved,
            'version' => 1,
            'pricing_rules' => [
                'base_price_per_intention' => '150.00',
                'service_fee' => [
                    'type' => 'percentage',
                    'amount' => '2.5', // 2.5%
                    'label' => 'Payment Processing (2.5%)',
                    'is_active' => true,
                ],
            ],
        ]);

        // 2 intentions = 300.00 subtotal
        // 2.5% of 300.00 = 7.50 fee
        // total = 307.50
        $result = $this->service->resolve($this->church, 2);

        $this->assertEquals('300.00', $result['slips_subtotal']);
        $this->assertEquals('7.50', $result['service_fee_amount']);
        $this->assertEquals('percentage', $result['service_fee_type']);
        $this->assertEquals('307.50', $result['total_amount']);
    }
}
