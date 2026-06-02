<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerClick extends \Illuminate\Database\Eloquent\Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'campaign_id',
        'marketer_id',
        'customer_id',
        'session_id',
        'ip_address',
        'referrer_url',
        'landing_product_id',
        'device_type',
        'country_id',
        'clicked_at',
        'converted',
        'conversion_order_id',
    ];

    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
            'converted' => 'boolean',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function conversionOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'conversion_order_id');
    }
}
