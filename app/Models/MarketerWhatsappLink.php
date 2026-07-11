<?php

namespace App\Models;

use App\Enums\MarketerWhatsappLinkType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerWhatsappLink extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'campaign_id',
        'link_type',
        'discount_pct',
        'free_shipping',
        'coupon_code',
        'whatsapp_message_template',
        'tracking_url',
        'qr_code_path',
        'share_count',
        'click_count',
        'conversion_count',
        'is_active',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_pct' => 'decimal:2',
            'free_shipping' => 'boolean',
            'is_active' => 'boolean',
            'share_count' => 'integer',
            'click_count' => 'integer',
            'conversion_count' => 'integer',
            'expires_at' => 'datetime',
            'link_type' => MarketerWhatsappLinkType::class,
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

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getWhatsappShareUrl(): string
    {
        $msg = str_replace(
            '{{link}}',
            urlencode($this->tracking_url),
            $this->whatsapp_message_template ?? $this->tracking_url
        );
        return 'https://wa.me/?text=' . urlencode($msg);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now() > $this->expires_at;
    }
}
