<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FbnInboundRequest extends Model
{
    use HasUuids;

    protected $table = 'fbn_inbound_requests';

    protected $fillable = [
        'request_number',
        'vendor_id',
        'vendor_listing_id',
        'warehouse_id',
        'quantity_requested',
        'quantity_received',
        'status',
        'admin_approved_by',
        'approved_at',
        'expected_arrival',
        'tracking_number',
        'rejection_reason',
        'vendor_notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expected_arrival' => 'date',
        'quantity_requested' => 'integer',
        'quantity_received' => 'integer',
    ];

    // ── Boot: auto-generate request_number ───────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->request_number)) {
                $year = now()->year;
                $seq = DB::table('fbn_inbound_requests')
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $model->request_number = 'FBN-' . $year . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function vendorListing(): BelongsTo
    {
        return $this->belongsTo(VendorListing::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_approved_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }
    public function scopePendingApproval($query)
    {
        return $query->whereIn('status', ['submitted']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'submitted' => 'warning',
            'approved' => 'primary',
            'shipped' => 'info',
            'received' => 'success',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'shipped' => 'Shipped',
            'received' => 'Received',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'submitted';
    }
    public function canBeRejected(): bool
    {
        return in_array($this->status, ['submitted', 'approved']);
    }
    public function canMarkShipped(): bool
    {
        return $this->status === 'approved';
    }
    public function canMarkReceived(): bool
    {
        return $this->status === 'shipped';
    }
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'submitted']);
    }
}
