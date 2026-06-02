<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCostReference extends Model
{
    use HasUuids;

    protected $table = 'product_cost_references';

    protected $fillable = [
        'product_id',
        'vendor_listing_id',
        'manufacturer_name',
        'manufacturer_url',
        'manufacturer_sku',
        'manufacturer_cost_cents',
        'shipping_cost_cents',
        'landed_cost_cents',
        'platform_margin_pct',
        'competitor_links',
        'competitor_last_checked',
        'notes',
        'is_confidential',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'manufacturer_cost_cents' => 'integer',
        'shipping_cost_cents' => 'integer',
        'landed_cost_cents' => 'integer',
        'platform_margin_pct' => 'decimal:2',
        'competitor_links' => 'array',
        'competitor_last_checked' => 'datetime',
        'is_confidential' => 'boolean',
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Factory price formatted (EGP). */
    public function manufacturerCostFormatted(): string
    {
        return $this->manufacturer_cost_cents !== null
            ? number_format($this->manufacturer_cost_cents / 100, 2) . ' EGP'
            : '—';
    }

    /** Shipping cost formatted. */
    public function shippingCostFormatted(): string
    {
        return $this->shipping_cost_cents !== null
            ? number_format($this->shipping_cost_cents / 100, 2) . ' EGP'
            : '—';
    }

    /** Landed cost formatted. */
    public function landedCostFormatted(): string
    {
        return $this->landed_cost_cents !== null
            ? number_format($this->landed_cost_cents / 100, 2) . ' EGP'
            : '—';
    }

    /**
     * Compute landed cost from components (factory + shipping).
     * Does NOT save — use this for display / pre-fill.
     */
    public function computedLandedCents(): ?int
    {
        if ($this->manufacturer_cost_cents === null && $this->shipping_cost_cents === null) {
            return null;
        }
        return (int) (($this->manufacturer_cost_cents ?? 0) + ($this->shipping_cost_cents ?? 0));
    }

    /**
     * Calculate margin percentage given a selling price in cents.
     * Returns null if landed_cost_cents is not set.
     */
    public function calculateMargin(int $sellingPriceCents): ?float
    {
        $landed = $this->landed_cost_cents ?? $this->computedLandedCents();
        if (!$landed || $sellingPriceCents <= 0) {
            return null;
        }
        return round(($sellingPriceCents - $landed) / $sellingPriceCents * 100, 2);
    }

    /**
     * True when a vendor listing's price is at or below landed cost.
     */
    public function isBelowLandedCost(int $sellingPriceCents): bool
    {
        $landed = $this->landed_cost_cents ?? $this->computedLandedCents();
        return $landed !== null && $sellingPriceCents <= $landed;
    }

    /**
     * Decoded competitor links, ensuring each entry has expected keys.
     */
    public function competitorLinksNormalized(): array
    {
        $links = $this->competitor_links ?? [];
        return array_map(fn($c) => [
            'name' => $c['name'] ?? '',
            'url' => $c['url'] ?? '',
            'price_cents' => isset($c['price_cents']) ? (int) $c['price_cents'] : null,
            'last_checked' => $c['last_checked'] ?? null,
        ], $links);
    }
}
