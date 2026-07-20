<?php

namespace App\Models;

use App\Enums\AdminInvitationCommissionType;
use App\Enums\AdminMarketerInvitationStatus;
use App\Enums\CampaignType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMarketerInvitation extends Model
{
    use HasUuids;

    protected $fillable = [
        'admin_id',
        'marketer_id',
        'title',
        'description',
        'campaign_type',
        'offered_commission_rate',
        'commission_type',
        'budget',
        'budget_currency',
        'starts_at',
        'ends_at',
        'expires_at',
        'status',
        'marketer_note',
        'responded_at',
        'resulting_campaign_id',
    ];

    protected $casts = [
        'offered_commission_rate' => 'integer',
        'budget' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'expires_at' => 'datetime',
        'responded_at' => 'datetime',
        'campaign_type' => CampaignType::class,
        'commission_type' => AdminInvitationCommissionType::class,
        'status' => AdminMarketerInvitationStatus::class,
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function resultingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class, 'resulting_campaign_id');
    }

    public function isExpired(): bool
    {
        return $this->status === AdminMarketerInvitationStatus::Pending
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function getCommissionRatePercentAttribute(): float
    {
        return $this->offered_commission_rate / 100;
    }
}
