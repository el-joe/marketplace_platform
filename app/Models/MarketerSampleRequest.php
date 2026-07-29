<?php

namespace App\Models;

use App\Enums\MarketerSampleRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketerSampleRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'marketer_id',
        'vendor_id',
        'campaign_id',
        'status',
        'notes',
        'admin_approved_by',
        'approved_at',
        'dispatched_at',
        'received_at',
        'rejection_reason',
        'requires_warehouse_receipt',
        'target_warehouse_id',
        'fulfillment_alert_sent',
        'fulfillment_alert_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MarketerSampleRequestStatus::class,
            'approved_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'received_at' => 'datetime',
            'requires_warehouse_receipt' => 'boolean',
            'fulfillment_alert_sent' => 'boolean',
            'fulfillment_alert_sent_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_approved_by');
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketerSampleItem::class, 'sample_request_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getTotalCostCentsAttribute(): int
    {
        return (int) $this->items->sum('sample_cost');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            MarketerSampleRequestStatus::Approved => 'success',
            MarketerSampleRequestStatus::Requested => 'warning',
            MarketerSampleRequestStatus::Dispatched => 'primary',
            MarketerSampleRequestStatus::Received => 'success',
            MarketerSampleRequestStatus::Rejected => 'danger',
            default => 'secondary',
        };
    }
}
