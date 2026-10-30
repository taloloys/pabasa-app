<?php

namespace App\Actions\Pricing;

use App\Enums\PricingStatus;
use App\Models\PricingConfiguration;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovePricingConfigAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * Approve a draft pricing configuration.
     * Retires any currently active approved config for the same scope/church.
     */
    public function execute(PricingConfiguration $config, User $actor, string $reason): PricingConfiguration
    {
        if ($config->status !== PricingStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => ['Only draft configurations can be approved.'],
            ]);
        }

        return DB::transaction(function () use ($config, $actor, $reason) {
            $before = $config->toArray();

            // Retire current approved config for the same scope/church
            PricingConfiguration::approved()
                ->when(
                    $config->scope === 'church',
                    fn ($q) => $q->where('scope', 'church')->where('church_id', $config->church_id),
                    fn ($q) => $q->where('scope', 'global')
                )
                ->where('id', '!=', $config->id)
                ->update(['status' => PricingStatus::Retired->value]);

            $config->update([
                'status' => PricingStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->audit->record(
                action: 'pricing_config.approved',
                target: $config,
                churchId: $config->church_id,
                reason: $reason,
                beforeData: $before,
                afterData: $config->fresh()->toArray(),
                actorUserId: $actor->id,
            );

            return $config->fresh();
        });
    }
}
