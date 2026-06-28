<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MarketerQrCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'campaign_id',
        'vendor_listing_id',
        'product_id',
        'code_type',
        'qr_code_path',
        'barcode_value',
        'custom_label',
        'scan_count',
        'last_scanned_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scan_count' => 'integer',
            'is_active' => 'boolean',
            'last_scanned_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class);
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getQrUrlAttribute(): ?string
    {
        return $this->qr_code_path;
    }

    public function getTypeLabel(): string
    {
        return match ($this->code_type) {
            'marketer_profile' => 'Profile',
            'product' => 'Product',
            'vendor_store' => 'Store',
            'campaign' => 'Campaign',
            'whatsapp_link' => 'WhatsApp',
            default => ucfirst($this->code_type),
        };
    }
}
