<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerConversion extends \Illuminate\Database\Eloquent\Model
{
    use HasUuids;

    protected $fillable = [
        'campaign_id',
        'marketer_id',
        'click_id',
        'order_id',
        'order_item_id',
        'customer_id',
        'vendor_id',
        'order_value_cents',
        'commission_rate',
        'commission_amount_cents',
        'currency',
        'status',
        'attribution_model',
        'click_chain',
        'is_whatsapp_conversion',
        'whatsapp_link_id',
        'approved_at',
        'paid_at',
        'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'reversed_at' => 'datetime',
            'commission_rate' => 'decimal:2',
            'order_value_cents' => 'integer',
            'commission_amount_cents' => 'integer',
            'click_chain' => 'array',
            'is_whatsapp_conversion' => 'boolean',
            'status' => \App\Enums\MarketerTrackingStatus::class,
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketerCampaign::class);
    }

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function click(): BelongsTo
    {
        return $this->belongsTo(MarketerClick::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function whatsappLink(): BelongsTo
    {
        return $this->belongsTo(MarketerWhatsappLink::class, 'whatsapp_link_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            \App\Enums\MarketerTrackingStatus::Approved => 'success',
            \App\Enums\MarketerTrackingStatus::Pending => 'warning',
            \App\Enums\MarketerTrackingStatus::Paid => 'primary',
            \App\Enums\MarketerTrackingStatus::Reversed => 'danger',
            default => 'secondary',
        };
    }
}
