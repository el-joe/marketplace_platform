<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorInfluencerPromotionRequestItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'promotion_request_id',
        'marketer_id',
        'status',
        'acceptance_window_hours',
        'expires_at',
        'responded_at',
        'marketer_note',
        'decline_reason',
        'slot_promotion_fee',
        'resulting_campaign_id',
        'qr_code_id',
        'notification_sent',
        'notification_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'acceptance_window_hours' => 'integer',
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
            'slot_promotion_fee' => 'integer',
            'notification_sent' => 'boolean',
            'notification_sent_at' => 'datetime',
        ];
    }

    public function promotionRequest(): BelongsTo
    {
        return $this->belongsTo(VendorInfluencerPromotionRequest::class, 'promotion_request_id');
    }

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function resultingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class, 'resulting_campaign_id');
    }

    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(MarketerQrCode::class, 'qr_code_id');
    }
}
