<?php

namespace App\Actions\Churches;

use App\Models\Church;
use App\Models\ChurchBranding;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class UpdateBrandingAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * @param  array{
     *     logo_path?: ?string,
     *     banner_path?: ?string,
     *     theme_color?: ?string,
     *     public_description?: ?string,
     *     contact_email?: ?string,
     *     contact_phone?: ?string,
     * }  $data
     */
    public function execute(Church $church, array $data, User $actor): ChurchBranding
    {
        return DB::transaction(function () use ($church, $data, $actor) {
            $branding = $church->branding ?? new ChurchBranding(['church_id' => $church->id]);

            $before = $branding->exists ? $branding->toArray() : null;

            $branding->fill($data);
            $branding->save();

            $this->audit->record(
                action: 'church.branding_updated',
                target: $branding,
                churchId: $church->id,
                reason: 'Church branding and presentation updated.',
                beforeData: $before,
                afterData: $branding->toArray(),
                actorUserId: $actor->id,
            );

            return $branding;
        });
    }
}
