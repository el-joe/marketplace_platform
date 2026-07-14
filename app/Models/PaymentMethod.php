<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'type',
        'gateway',
        'gateway_token',
        'card_brand',
        'card_last4',
        'card_exp_month',
        'card_exp_year',
        'billing_address_id',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentMethodType::class,
            'is_default' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function getCardDisplayAttribute(): ?string
    {
        if (!$this->card_brand || !$this->card_last4) {
            return null;
        }

        return "{$this->card_brand} \u{2022}\u{2022}\u{2022}\u{2022} {$this->card_last4}";
    }
}
