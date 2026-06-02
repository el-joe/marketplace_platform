<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketerSampleItem extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'sample_request_id',
        'vendor_listing_id',
        'quantity',
        'is_mandatory',
        'sample_cost_cents',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_mandatory' => 'boolean',
            'sample_cost_cents' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function sampleRequest(): BelongsTo
    {
        return $this->belongsTo(MarketerSampleRequest::class, 'sample_request_id');
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class, 'vendor_listing_id');
    }
}
