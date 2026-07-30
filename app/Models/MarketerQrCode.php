<?php

namespace App\Models;

use App\Enums\MarketerQrCodeType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MarketerQrCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'previous_marketer_id',
        'campaign_id',
        'promotion_request_item_id',
        'vendor_listing_id',
        'admin_product_listing_id',
        'product_id',
        'code_type',
        'qr_code_path',
        'barcode_value',
        'custom_label',
        'scan_count',
        'last_scanned_at',
        'is_active',
        'reassigned_at',
    ];

    protected function casts(): array
    {
        return [
            'scan_count' => 'integer',
            'is_active' => 'boolean',
            'last_scanned_at' => 'datetime',
            'reassigned_at' => 'datetime',
            'code_type' => MarketerQrCodeType::class,
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function previousMarketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class, 'previous_marketer_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class);
    }

    public function promotionRequestItem(): BelongsTo
    {
        return $this->belongsTo(VendorInfluencerPromotionRequestItem::class, 'promotion_request_item_id');
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function adminProductListing(): BelongsTo
    {
        return $this->belongsTo(AdminProductListing::class);
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
        return $this->code_type->label();
    }
}
