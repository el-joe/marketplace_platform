<?php

namespace App\Models;

use App\Enums\CountryPaymentMethodEnvironment;
use App\Enums\CountryPaymentMethodVerificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class CountryPaymentMethod extends Model
{
    use HasUuids;

    protected $table = 'country_payment_methods';
    protected $keyType = 'string';
    public $incrementing = false;

    // credentials_encrypted and webhook_secret_encrypted are intentionally excluded
    // from fillable — use setCredentials() / setWebhookSecret() to force encryption
    protected $fillable = [
        'id',
        'country_id',
        'method_type',
        'provider',
        'gateway_code',
        'display_name_en',
        'display_name_ar',
        'is_active',
        'fee_pct',
        'fee_fixed',
        'min_order',
        'max_order',
        'settlement_currency',
        'environment',
        'sort_order',
        'installments_count',
        'installment_label_en',
        'installment_label_ar',
        'provider_logo_path',
        'learn_more_url',
        'last_verified_at',
        'last_verification_status',
        'last_verification_message',
    ];

    // Prevent credentials leaking into JSON responses, logs, or Blade dumps
    protected $hidden = [
        'credentials_encrypted',
        'webhook_secret_encrypted',
    ];

    /** @var int Base currency unit (BIGINT) for money fields renamed in this model */
    protected $casts = [
        'is_active'                 => 'boolean',
        'fee_pct'                   => 'decimal:2',
        'fee_fixed'           => 'integer',
        'min_order'           => 'integer',
        'max_order'           => 'integer',
        'sort_order'                => 'integer',
        'installments_count'        => 'integer',
        'environment'               => CountryPaymentMethodEnvironment::class,
        'last_verified_at'          => 'datetime',
        'last_verification_status' => CountryPaymentMethodVerificationStatus::class,
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(PaymentGatewayWebhookLog::class);
    }

    // ── Credential accessors/mutators (encrypted at rest) ─────────────────────

    public function setCredentials(array $credentials): void
    {
        $this->credentials_encrypted = Crypt::encryptString(json_encode($credentials));
        $this->save();
    }

    public function getCredentials(): array
    {
        if (!$this->credentials_encrypted) {
            return [];
        }
        try {
            return json_decode(Crypt::decryptString($this->credentials_encrypted), true) ?? [];
        } catch (\Exception $e) {
            report($e);
            return [];
        }
    }

    public function setWebhookSecret(string $secret): void
    {
        $this->webhook_secret_encrypted = Crypt::encryptString($secret);
        $this->save();
    }

    public function getWebhookSecret(): ?string
    {
        if (!$this->webhook_secret_encrypted) {
            return null;
        }
        try {
            return Crypt::decryptString($this->webhook_secret_encrypted);
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * The currency transactions will actually be charged in.
     * settlement_currency override wins over country default.
     */
    public function getEffectiveCurrencyAttribute(): string
    {
        return $this->settlement_currency ?? $this->country?->currency_code ?? 'USD';
    }

    public function getIsConfiguredAttribute(): bool
    {
        return !empty($this->getCredentials());
    }

    public function getIsInstallmentEnabledAttribute(): bool
    {
        return $this->installments_count > 1;
    }

    // ── Formatted helpers ─────────────────────────────────────────────────────

    public function getFeeFixedFormattedAttribute(): string
    {
        return number_format($this->fee_fixed / 100, 2);
    }

    public function getMinOrderFormattedAttribute(): string
    {
        return number_format($this->min_order / 100, 2);
    }

    public function getMaxOrderFormattedAttribute(): ?string
    {
        return $this->max_order
            ? number_format($this->max_order / 100, 2)
            : null;
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function scopeForCountry($query, string $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    public function scopeByGateway($query, string $gatewayCode)
    {
        return $query->where('gateway_code', $gatewayCode);
    }
}
