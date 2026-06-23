<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FbnStorageFee extends Model
{
    use HasUuids;

    protected $table = 'fbn_storage_fees';

    protected $fillable = [
        'vendor_id',
        'warehouse_inventory_id',
        'month',
        'units_stored',
        'rate_per_unit_cents',
        'total_fee_cents',
        'currency',
        'status',
    ];

    protected $casts = [
        'month' => 'date',
        'units_stored' => 'integer',
        'rate_per_unit_cents' => 'integer',
        'total_fee_cents' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function warehouseInventory(): BelongsTo
    {
        return $this->belongsTo(WarehouseInventory::class, 'warehouse_inventory_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    public function scopeInvoiced($query)
    {
        return $query->where('status', 'invoiced');
    }
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForMonth($query, string $month)
    {
        return $query->whereDate('month', $month);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'invoiced' => 'primary',
            'paid' => 'success',
            default => 'secondary',
        };
    }

    public function totalFormatted(): string
    {
        return number_format($this->total_fee_cents / 100, 2) . ' ' . $this->currency;
    }

    public function monthLabel(): string
    {
        return $this->month?->format('F Y') ?? '—';
    }
}
