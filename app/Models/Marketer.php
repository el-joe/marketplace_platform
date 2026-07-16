<?php

namespace App\Models;

use App\Enums\MarketerTrackingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Marketer extends Authenticatable implements JWTSubject
{
    use HasUuids, SoftDeletes, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'type',
        'bio',
        'profile_photo_path',
        'country_id',
        'social_instagram',
        'social_tiktok',
        'social_youtube',
        'social_twitter',
        'social_facebook',
        'followers_count',
        'engagement_rate',
        'niche',
        'status',
        'referral_code',
        'commission_rate',
        'total_earnings',
        'total_clicks',
        'total_conversions',
        'bank_name',
        'bank_iban',
        'bank_account_name',
        'approved_by_admin_id',
        'approved_at',
        'last_login_at',
        'profile_video_url',
        'profile_banner_path',
        'promo_content',
        'boutiqaat_style_slug',
        'whatsapp_number',
        'total_samples_used',
        'total_samples_allocated',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
            'engagement_rate' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'total_earnings' => 'integer',
            'total_clicks' => 'integer',
            'total_conversions' => 'integer',
            'followers_count' => 'integer',
            'status' => \App\Enums\MarketerStatus::class,
            'type' => \App\Enums\MarketerType::class,
        ];
    }

    // ── JWTSubject ────────────────────────────────────────────────────────────

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return ['guard' => 'marketer_api'];
    }

    // ── Boot: generate referral_code + boutiqaat_style_slug ───────────────────

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $marketer): void {
            if (empty($marketer->referral_code)) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (self::withTrashed()->where('referral_code', $code)->exists());
                $marketer->referral_code = $code;
            }

            if (empty($marketer->boutiqaat_style_slug) && !empty($marketer->name)) {
                $base = Str::slug($marketer->name);
                $slug = $base;
                $i = 1;
                while (self::withTrashed()->where('boutiqaat_style_slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $marketer->boutiqaat_style_slug = $slug;
            }
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketerCampaign::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(MarketerClick::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(MarketerConversion::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(MarketerPayout::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(MarketerQrCode::class);
    }

    public function whatsappLinks(): HasMany
    {
        return $this->hasMany(MarketerWhatsappLink::class);
    }

    public function sampleRequests(): HasMany
    {
        return $this->hasMany(MarketerSampleRequest::class);
    }

    public function commissionTiers(): HasMany
    {
        return $this->hasMany(MarketerCommissionTier::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? '';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            \App\Enums\MarketerStatus::Active => 'success',
            \App\Enums\MarketerStatus::Pending => 'warning',
            \App\Enums\MarketerStatus::Suspended => 'danger',
            \App\Enums\MarketerStatus::Rejected => 'secondary',
            default => 'secondary',
        };
    }

    public function getPendingEarningsCentsAttribute(): int
    {
        return $this->conversions()
            ->whereIn('status', [MarketerTrackingStatus::Pending, MarketerTrackingStatus::Approved])
            ->sum('commission_amount');
    }

    public function getCurrentTierRate(int $salesCount): float
    {
        $tier = MarketerCommissionTier::where('marketer_id', $this->id)
            ->active()
            ->forSalesCount($salesCount)
            ->first();

        return (float) ($tier?->commission_rate ?? $this->commission_rate ?? 0);
    }

    public function getTotalPendingEarnings(): int
    {
        return (int) $this->conversions()
            ->where('status', MarketerTrackingStatus::Pending)
            ->sum('commission_amount');
    }

    public function getProfileUrl(): string
    {
        return route('marketer.profile.public', $this->boutiqaat_style_slug ?? $this->id);
    }

    public function scopeActive($q)
    {
        return $q->where('status', \App\Enums\MarketerStatus::Active);
    }

    public function scopePending($q)
    {
        return $q->where('status', \App\Enums\MarketerStatus::Pending);
    }
}
