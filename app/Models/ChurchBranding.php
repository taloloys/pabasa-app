<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChurchBranding extends Model
{
    use HasFactory;

    protected $table = 'church_branding';

    protected $fillable = [
        'church_id',
        'logo_path',
        'banner_path',
        'theme_color',
        'public_description',
        'contact_email',
        'contact_phone',
        'gcash_name',
        'gcash_number',
        'gcash_qr_path',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the full public URL for the GCash QR code image, or null if not uploaded.
     */
    public function getGcashQrUrlAttribute(): ?string
    {
        if (! $this->gcash_qr_path) {
            return null;
        }

        return Storage::disk('public')->url($this->gcash_qr_path);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
