<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftCard extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'denomination',
        'currency',
        'balance',
        'status',
        'purchased_by_customer_id',
        'recipient_email',
        'recipient_phone',
        'recipient_name',
        'personal_message',
        'created_by_admin_id',
        'activated_at',
        'expires_at',
    ];

    /** @var int Base currency unit (BIGINT) for money fields renamed in this model (dropped legacy _cents suffix) */
    protected $casts = [
        'denomination' => 'integer',
        'balance' => 'integer',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now())
            ->where('balance', '>', 0);
    }

    public function purchasedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'purchased_by_customer_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class);
    }
}
