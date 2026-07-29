<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class VendorInfluencerReassignmentLog extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $table = 'vendor_influencer_reassignment_log';

    protected $fillable = [
        'promotion_request_id',
        'promotion_request_item_id',
        'from_marketer_id',
        'to_marketer_id',
        'reason',
        'to_marketer_request_count_30d',
        'triggered_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'to_marketer_request_count_30d' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new RuntimeException('VendorInfluencerReassignmentLog is append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new RuntimeException('VendorInfluencerReassignmentLog is append-only and cannot be deleted.');
        });
    }

    public function promotionRequest(): BelongsTo
    {
        return $this->belongsTo(VendorInfluencerPromotionRequest::class, 'promotion_request_id');
    }

    public function promotionRequestItem(): BelongsTo
    {
        return $this->belongsTo(VendorInfluencerPromotionRequestItem::class, 'promotion_request_item_id');
    }

    public function fromMarketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class, 'from_marketer_id');
    }

    public function toMarketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class, 'to_marketer_id');
    }

    public function triggeredByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'triggered_by_admin_id');
    }
}
