<?php

namespace App\Services\Pricing;

use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\PricingConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PricingResolutionService
{
    /**
     * Resolve approved pricing for a church and calculate total for the given intention count or intention items.
     *
     * @param  int|array<array{category_id?: int}>  $intentions
     * @param  iterable<int, ChurchCategory>|null  $preloadedCategories
     * @return array{
     *     configuration: PricingConfiguration,
     *     total_amount: string,
     *     slips_subtotal: string,
     *     service_fee_amount: string,
     *     service_fee_type: string,
     *     service_fee_label: string,
     *     currency: string,
     *     snapshot: array<string, mixed>
     * }
     *
     * @throws ValidationException
     */
    public function resolve(Church $church, int|array $intentions, ?iterable $preloadedCategories = null): array
    {
        // Spec rule: "No approved pricing configuration produces the defined configuration-required behavior; no price is invented."
        $config = PricingConfiguration::approved()
            ->forChurch($church->id)
            ->where(function ($q) {
                $q->whereNull('effective_from')->orWhere('effective_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('effective_until')->orWhere('effective_until', '>=', now());
            })
            ->latest('version')
            ->first();

        if (! $config) {
            throw ValidationException::withMessages([
                'pricing' => ['configuration_required: No approved pricing configuration is currently active for this parish. Intention submission is temporarily blocked until approved by administration.'],
            ]);
        }

        $rules = $config->pricing_rules ?? [];
        $pricePerIntention = (string) ($rules['base_price_per_intention'] ?? '0.00');

        $categoryPrices = [];
        if (is_array($intentions)) {
            $intentionCount = count($intentions);
            $slipsSubtotal = '0.00';

            $categoriesById = $preloadedCategories instanceof Collection
                ? $preloadedCategories
                : ($preloadedCategories !== null ? collect($preloadedCategories)->keyBy('id') : null);

            if ($categoriesById === null) {
                $catIds = collect($intentions)->pluck('category_id')->filter()->unique()->all();
                $categoriesById = ! empty($catIds)
                    ? ChurchCategory::whereIn('id', $catIds)->get()->keyBy('id')
                    : collect();
            }

            foreach ($intentions as $item) {
                $catId = isset($item['category_id']) ? (int) $item['category_id'] : null;
                $itemPrice = $pricePerIntention;

                if ($catId) {
                    $category = $categoriesById->get($catId);
                    if ($category && $category->price !== null) {
                        $itemPrice = (string) $category->price;
                    }
                    $categoryPrices[$catId] = $itemPrice;
                }

                $slipsSubtotal = bcadd($slipsSubtotal, $itemPrice, 2);
            }
        } else {
            $intentionCount = (int) $intentions;
            // Non-negotiable financial rule: Decimal-safe arithmetic with bcmath, NEVER float
            $slipsSubtotal = bcmul($pricePerIntention, (string) $intentionCount, 2);
        }

        // Resolve configurable service fee — all arithmetic is bcmath, never float
        [$serviceFeeAmount, $serviceFeeType, $serviceFeeLabel] = $this->resolveServiceFee(
            $rules,
            $slipsSubtotal,
            $intentionCount
        );

        $totalAmount = bcadd($slipsSubtotal, $serviceFeeAmount, 2);

        $snapshot = [
            'configuration_id' => $config->id,
            'version' => $config->version,
            'currency' => $config->currency,
            'slips_count' => $intentionCount,
            'price_per_intention' => $pricePerIntention,
            'category_prices' => $categoryPrices,
            'slips_subtotal' => $slipsSubtotal,
            'service_fee' => [
                'type' => $serviceFeeType,
                'rate' => $rules['service_fee']['amount'] ?? '0.00',
                'calculated_amount' => $serviceFeeAmount,
                'label' => $serviceFeeLabel,
            ],
            'total_amount' => $totalAmount,
            'rules' => $rules,
            'calculated_at' => now()->toIso8601String(),
        ];

        return [
            'configuration' => $config,
            'total_amount' => $totalAmount,
            'slips_subtotal' => $slipsSubtotal,
            'service_fee_amount' => $serviceFeeAmount,
            'service_fee_type' => $serviceFeeType,
            'service_fee_label' => $serviceFeeLabel,
            'currency' => $config->currency,
            'snapshot' => $snapshot,
        ];
    }

    /**
     * Resolve the service fee from pricing rules.
     *
     * Supported types:
     *   - 'fixed_per_order':   Flat fee applied once per order regardless of slip count.
     *   - 'fixed_per_slip':    Flat fee multiplied by total number of slips.
     *   - 'percentage':        Percentage of the slips subtotal (bcmath-safe).
     *   - 'none' (default):    No fee applied.
     *
     * @param  array<string, mixed>  $rules
     * @return array{0: string, 1: string, 2: string} [feeAmount, feeType, feeLabel]
     */
    protected function resolveServiceFee(array $rules, string $slipsSubtotal, int $slipCount): array
    {
        $feeCfg = $rules['service_fee'] ?? [];
        $feeType = (string) ($feeCfg['type'] ?? 'none');
        $feeRate = (string) ($feeCfg['amount'] ?? '0.00');
        $feeLabel = (string) ($feeCfg['label'] ?? 'Platform Convenience Fee');

        if (! ($feeCfg['is_active'] ?? false)) {
            return ['0.00', 'none', $feeLabel];
        }

        $feeAmount = match ($feeType) {
            'fixed_per_order' => $feeRate,
            'fixed_per_slip' => bcmul($feeRate, (string) $slipCount, 2),
            'percentage' => bcmul($slipsSubtotal, bcdiv($feeRate, '100', 4), 2),
            default => '0.00',
        };

        return [$feeAmount, $feeType, $feeLabel];
    }
}
