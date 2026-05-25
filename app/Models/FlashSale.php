<?php

namespace App\Models;

use App\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSale extends Model
{
    use HasUuids, HasStateMachine;

    // ── State machine ─────────────────────────────────────────────────────────

    public const STATUS_TRANSITIONS = [
        'draft'     => ['open', 'cancelled'],
        'open'      => ['review', 'cancelled'],
        'review'    => ['scheduled', 'cancelled'],
        'scheduled' => ['live', 'cancelled'],
        'live'      => ['ended'],
        'ended'     => [],
        'cancelled' => [],
    ];

    public const STATUS_LABELS = [
        'draft'     => 'Draft',
        'open'      => 'Submissions Open',
        'review'    => 'Under Review',
        'scheduled' => 'Scheduled',
        'live'      => 'Live',
        'ended'     => 'Ended',
        'cancelled' => 'Cancelled',
    ];

    // ── Fillable ──────────────────────────────────────────────────────────────

    protected $fillable = [
        'country_id',
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'banner_file_id',
        'status',
        'submission_opens_at',
        'submission_closes_at',
        'review_deadline_at',
        'sale_starts_at',
        'sale_ends_at',
        'min_discount_pct',
        'max_products_per_vendor',
        'eligible_categories',
        'eligible_vendor_tiers',
        'min_vendor_rating',
        'commission_override_pct',
        'is_featured',
        'is_exclusive',
        'price_drop_required',
        'max_total_slots',
        'approved_slots_count',
        'created_by_admin_id',
        'updated_by_admin_id',
    ];

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'eligible_categories'  => 'array',
            'eligible_vendor_tiers' => 'array',
            'submission_opens_at'  => 'datetime',
            'submission_closes_at' => 'datetime',
            'review_deadline_at'   => 'datetime',
            'sale_starts_at'       => 'datetime',
            'sale_ends_at'         => 'datetime',
            'min_discount_pct'     => 'decimal:2',
            'min_vendor_rating'    => 'decimal:2',
            'commission_override_pct' => 'decimal:2',
            'is_featured'          => 'boolean',
            'is_exclusive'         => 'boolean',
            'price_drop_required'  => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function bannerFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'banner_file_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }

    public function vendorInvitations(): HasMany
    {
        return $this->hasMany(FlashSaleVendorInvitition::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FlashSaleSubmission::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(FlashSaleAnalytics::class);
    }
}
