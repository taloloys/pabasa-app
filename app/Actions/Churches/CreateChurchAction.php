<?php

namespace App\Actions\Churches;

use App\Enums\CategoryType;
use App\Enums\ChurchStatus;
use App\Enums\MembershipRole;
use App\Models\Church;
use App\Models\ChurchBranding;
use App\Models\ChurchCategory;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateChurchAction
{
    public function __construct(
        protected AuditService $auditService,
        protected CreateInvitationAction $invitationAction,
    ) {}

    /**
     * Canonically register a new parish.
     *
     * @param  array{
     *     name: string,
     *     slug?: ?string,
     *     contact_email?: ?string,
     *     contact_phone?: ?string,
     *     address?: ?string,
     *     city: string,
     *     province: string,
     *     postal_code?: ?string,
     *     timezone?: ?string,
     *     auto_verify?: bool,
     *     initial_admin_email?: ?string,
     * }  $data
     * @return array{church: Church, invitation: ?array}
     *
     * @throws AuthorizationException
     */
    public function execute(array $data, User $actor): array
    {
        if (! $actor->isCentralAdmin()) {
            throw new AuthorizationException('Only Central Administrators are authorized to register new churches.');
        }

        return DB::transaction(function () use ($data, $actor) {
            // Generate unique slug
            $baseSlug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);
            if (empty($baseSlug)) {
                $baseSlug = 'parish-'.Str::lower(Str::random(6));
            }

            $slug = $baseSlug;
            $counter = 1;
            while (Church::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-".Str::slug($data['city'])."-{$counter}";
                $counter++;
            }

            $autoVerify = ! empty($data['auto_verify']);
            $status = $autoVerify ? ChurchStatus::Verified : ChurchStatus::Draft;

            $church = Church::create([
                'name' => trim($data['name']),
                'slug' => $slug,
                'status' => $status,
                'contact_email' => ! empty($data['contact_email']) ? strtolower(trim($data['contact_email'])) : null,
                'contact_phone' => ! empty($data['contact_phone']) ? trim($data['contact_phone']) : null,
                'address' => ! empty($data['address']) ? trim($data['address']) : null,
                'city' => trim($data['city']),
                'province' => trim($data['province']),
                'postal_code' => ! empty($data['postal_code']) ? trim($data['postal_code']) : null,
                'timezone' => $data['timezone'] ?? 'Asia/Manila',
                'verified_at' => $autoVerify ? now() : null,
                'verified_by' => $autoVerify ? $actor->id : null,
            ]);

            // Initialize 1-to-1 default Branding
            ChurchBranding::create([
                'church_id' => $church->id,
                'theme_color' => '#78350f',
                'contact_email' => $church->contact_email,
                'contact_phone' => $church->contact_phone,
                'public_description' => "Welcome to {$church->name}.",
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ]);

            // Seed canonical default intention categories
            $defaultCategories = [
                [
                    'key' => 'thanksgiving',
                    'label' => 'Thanksgiving',
                    'description' => 'Prayers of gratitude for blessings received, birthdays, and anniversaries.',
                    'color' => '#b45309',
                    'sort_order' => 1,
                ],
                [
                    'key' => 'souls',
                    'label' => 'Souls of the Faithful Departed',
                    'description' => 'Requiem prayers for eternal repose of the faithful departed.',
                    'color' => '#4338ca',
                    'sort_order' => 2,
                ],
                [
                    'key' => 'healing',
                    'label' => 'Healing & Recovery',
                    'description' => 'Prayers for physical, mental, and spiritual health and recovery from illness.',
                    'color' => '#047857',
                    'sort_order' => 3,
                ],
                [
                    'key' => 'petition',
                    'label' => 'Special Petition',
                    'description' => 'Petitions for peace, guidance, safe journeys, and special intentions.',
                    'color' => '#7c3aed',
                    'sort_order' => 4,
                ],
            ];

            foreach ($defaultCategories as $cat) {
                ChurchCategory::create([
                    'church_id' => $church->id,
                    'type' => CategoryType::Intention,
                    'key' => $cat['key'],
                    'label' => $cat['label'],
                    'description' => $cat['description'],
                    'color' => $cat['color'],
                    'is_active' => true,
                    'is_system' => false,
                    'sort_order' => $cat['sort_order'],
                ]);
            }

            // Optional: Dispatch initial Church Admin staff invitation
            $invitationResult = null;
            if (! empty($data['initial_admin_email'])) {
                $invitationResult = $this->invitationAction->execute(
                    church: $church,
                    email: $data['initial_admin_email'],
                    role: MembershipRole::ChurchAdmin,
                    invitedBy: $actor,
                    reason: 'Initial parish administrator invitation during registration'
                );
            }

            // Canonical Audit Log
            $this->auditService->record(
                action: 'church.created',
                target: $church,
                churchId: $church->id,
                reason: 'Canonically registered new parish via Central Diocese Console.',
                afterData: [
                    'name' => $church->name,
                    'slug' => $church->slug,
                    'city' => $church->city,
                    'province' => $church->province,
                    'status' => $church->status->value,
                    'initial_admin_email' => $data['initial_admin_email'] ?? null,
                ],
                actorUserId: $actor->id,
            );

            return [
                'church' => $church,
                'invitation' => $invitationResult,
            ];
        });
    }
}
