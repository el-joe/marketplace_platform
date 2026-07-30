<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CelebrityStoreProduct extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'celebrity_store_products';

    protected $fillable = [
        'celebrity_marketer_id',
        'vendor_listing_id',
        'admin_product_listing_id',
        'promoter_commission_amount',
        'currency_code',
        'admin_commission_pct_bps',
        'is_active',
        'approved_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'promoter_commission_amount' => 'integer',
            'admin_commission_pct_bps' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function celebrity(): BelongsTo
    {
        return $this->belongsTo(Marketer::class, 'celebrity_marketer_id');
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function adminListing(): BelongsTo
    {
        return $this->belongsTo(AdminProductListing::class, 'admin_product_listing_id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(CelebrityOpenMarketPromotion::class);
    }
}
