<?php

namespace App\Actions\Pricing;

use App\Enums\PricingStatus;
use App\Models\PricingConfiguration;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CreatePricingConfigAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * @param  array{
     *     scope: string,
     *     church_id?: ?int,
     *     name: string,
     *     currency: string,
     *     effective_from?: ?string,
     *     effective_until?: ?string,
     *     base_price_per_intention?: ?string,
     *     service_fee_type: string,
     *     service_fee_amount?: ?string,
     *     service_fee_label?: ?string,
     *     service_fee_active: bool,
     *     change_reason: string,
     * }  $data
     */
    public function execute(array $data, User $actor): PricingConfiguration
    {
        return DB::transaction(function () use ($data, $actor) {
            // Determine version: next version after the latest draft or approved config for the same scope/church
            $latestVersion = PricingConfiguration::query()
                ->when(
                    ($data['scope'] ?? 'global') === 'church' && ! empty($data['church_id']),
                    fn ($q) => $q->where('scope', 'church')->where('church_id', $data['church_id']),
                    fn ($q) => $q->where('scope', 'global')
                )
                ->max('version') ?? 0;

            $pricingRules = [
                'base_price_per_intention' => $data['base_price_per_intention'] ?? null,
                'category_prices' => [],
                'service_fee' => [
                    'type' => $data['service_fee_type'],
                    'amount' => $data['service_fee_amount'] ?? '0.00',
                    'label' => $data['service_fee_label'] ?? 'Platform Convenience Fee',
                    'is_active' => (bool) ($data['service_fee_active'] ?? false),
                ],
            ];

            $config = PricingConfiguration::create([
                'scope' => $data['scope'] ?? 'global',
                'church_id' => $data['church_id'] ?? null,
                'name' => $data['name'],
                'currency' => $data['currency'] ?? 'PHP',
                'status' => PricingStatus::Draft,
                'effective_from' => $data['effective_from'] ?? null,
                'effective_until' => $data['effective_until'] ?? null,
                'pricing_rules' => $pricingRules,
                'change_reason' => $data['change_reason'],
                'version' => $latestVersion + 1,
            ]);

            $this->audit->record(
                action: 'pricing_config.created',
                target: $config,
                churchId: $config->church_id,
                reason: $data['change_reason'],
                afterData: $config->toArray(),
                actorUserId: $actor->id,
            );

            return $config;
        });
    }
}
