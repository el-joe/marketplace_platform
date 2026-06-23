<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MarketerPayout extends \Illuminate\Database\Eloquent\Model
{
    use HasUuids;

    protected $fillable = [
        'payout_number',
        'marketer_id',
        'period_start',
        'period_end',
        'total_conversions',
        'gross_commission_cents',
        'tax_deduction_cents',
        'net_amount_cents',
        'currency',
        'status',
        'bank_name',
        'bank_iban',
        'approved_by_admin_id',
        'processed_at',
        'payment_reference',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'processed_at' => 'datetime',
            'total_conversions' => 'integer',
            'gross_commission_cents' => 'integer',
            'tax_deduction_cents' => 'integer',
            'net_amount_cents' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $payout): void {
            if (empty($payout->payout_number)) {
                $payout->payout_number = 'PAY-MKT-' . strtoupper(Str::random(8));
            }
        });
    }

    public function marketer(): BelongsTo
    {
        return $this->belongsTo(Marketer::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'approved' => 'primary',
            'pending' => 'warning',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
