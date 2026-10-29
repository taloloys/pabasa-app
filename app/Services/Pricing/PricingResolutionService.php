<?php

namespace App\Services\Pricing;

use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\PricingConfiguration;
use Illuminate\Validation\ValidationException;

class PricingResolutionService
{
    /**
     * Resolve approved pricing for a church and calculate total for the given intention count or intention items.
     *
     * @param  int|array<array{category_id?: int}>  $intentions
     * @return array{
     *     configuration: PricingConfiguration,
     *     total_amount: string,
     *     currency: string,
     *     snapshot: array<string, mixed>
     * }
     *
     * @throws ValidationException
     */
    public function resolve(Church $church, int|array $intentions): array
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
            $total = '0.00';

            foreach ($intentions as $item) {
                $catId = isset($item['category_id']) ? (int) $item['category_id'] : null;
                $itemPrice = $pricePerIntention;

                if ($catId) {
                    $category = ChurchCategory::find($catId);
                    if ($category && $category->price !== null) {
                        $itemPrice = (string) $category->price;
                    }
                    $categoryPrices[$catId] = $itemPrice;
                }

                $total = bcadd($total, $itemPrice, 2);
            }
        } else {
            $intentionCount = (int) $intentions;
            // Non-negotiable financial rule: Decimal-safe arithmetic with bcmath, NEVER float
            $total = bcmul($pricePerIntention, (string) $intentionCount, 2);
        }

        $snapshot = [
            'configuration_id' => $config->id,
            'version' => $config->version,
            'currency' => $config->currency,
            'price_per_intention' => $pricePerIntention,
            'category_prices' => $categoryPrices,
            'intention_count' => $intentionCount,
            'total_amount' => $total,
            'rules' => $rules,
        ];

        return [
            'configuration' => $config,
            'total_amount' => $total,
            'currency' => $config->currency,
            'snapshot' => $snapshot,
        ];
    }
}
