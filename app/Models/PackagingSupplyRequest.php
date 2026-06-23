<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackagingSupplyRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'request_number',
        'vendor_id',
        'warehouse_id',
        'status',
        'total_cost_cents',
        'notes',
        'approved_by_admin_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'total_cost_cents' => 'integer',
            'approved_at'      => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackagingSupplyRequestItem::class, 'request_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function getTotalCostFormattedAttribute(): string
    {
        return $this->total_cost_cents === 0
            ? 'Free'
            : number_format($this->total_cost_cents / 100, 2);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function statusBadgeClass(): string
    {
        return match($this->status) {
            'pending'   => 'bg-yellow-100 text-yellow-800',
            'approved'  => 'bg-blue-100 text-blue-800',
            'shipped'   => 'bg-indigo-100 text-indigo-800',
            'delivered' => 'bg-green-100 text-green-800',
            'rejected'  => 'bg-red-100 text-red-800',
            default     => 'bg-gray-100 text-gray-800',
        };
    }

    public static function generateRequestNumber(): string
    {
        return 'PKG-' . strtoupper(substr(uniqid(), -8));
    }
}
