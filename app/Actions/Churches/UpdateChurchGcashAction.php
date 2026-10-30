<?php

namespace App\Actions\Churches;

use App\Models\Church;
use App\Models\ChurchBranding;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateChurchGcashAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * @param  array{
     *     gcash_name?: ?string,
     *     gcash_number?: ?string,
     *     gcash_qr_image?: ?UploadedFile,
     * }  $data
     */
    public function execute(Church $church, array $data, User $actor): ChurchBranding
    {
        return DB::transaction(function () use ($church, $data, $actor) {
            $branding = $church->branding ?? new ChurchBranding(['church_id' => $church->id]);
            $before = $branding->exists ? $branding->only(['gcash_name', 'gcash_number', 'gcash_qr_path']) : null;

            // Handle QR code image upload to public disk
            if (isset($data['gcash_qr_image']) && $data['gcash_qr_image'] instanceof UploadedFile) {
                // Delete old QR image if present
                if ($branding->gcash_qr_path) {
                    Storage::disk('public')->delete($branding->gcash_qr_path);
                }

                $extension = $data['gcash_qr_image']->getClientOriginalExtension();
                $filename = 'churches/qr/church_'.$church->id.'_gcash_'.Str::random(12).'.'.$extension;
                Storage::disk('public')->putFileAs('', $data['gcash_qr_image'], $filename);
                $branding->gcash_qr_path = $filename;
            }

            if (array_key_exists('gcash_name', $data)) {
                $branding->gcash_name = $data['gcash_name'];
            }

            if (array_key_exists('gcash_number', $data)) {
                $branding->gcash_number = $data['gcash_number'];
            }

            $branding->save();

            $this->audit->record(
                action: 'church.gcash_settings_updated',
                target: $branding,
                churchId: $church->id,
                reason: 'GCash payment credentials updated.',
                beforeData: $before,
                afterData: $branding->only(['gcash_name', 'gcash_number', 'gcash_qr_path']),
                actorUserId: $actor->id,
            );

            return $branding;
        });
    }
}
