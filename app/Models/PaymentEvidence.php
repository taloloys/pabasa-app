<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvidence extends Model
{
    use HasFactory;

    protected $table = 'payment_evidence';

    protected $fillable = [
        'payment_id',
        'church_id',
        'storage_disk',
        'storage_key',
        'original_filename',
        'mime_type',
        'size_bytes',
        'checksum',
        'uploaded_by_user_id',
        'uploaded_by_guest',
        'evidence_status',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_by_guest' => 'boolean',
            'size_bytes' => 'integer',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
