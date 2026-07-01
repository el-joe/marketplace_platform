<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TravelPackage extends Model
{
    use HasUuids;

    protected $fillable = [
        'travel_agency_id',
        'slug',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'destination_country',
        'destination_city',
        'destination_travel_country_id',
        'destination_travel_city_id',
        'price_cents',
        'currency',
        'duration_days',
        'duration_nights',
        'departure_date',
        'return_date',
        'available_seats',
        'seats_booked',
        'inclusions',
        'status',
        'approved_by_admin_id',
        'approved_at',
        'rejection_reason',
        'contract_file_path',
        'contract_file_original_name',
        'contract_uploaded_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $package) {
            $base = Str::slug($package->title_en ?? 'package');
            do {
                $slug = $base . '-' . Str::lower(Str::random(6));
            } while (static::where('slug', $slug)->exists());
            $package->slug = $slug;
        });
    }

    protected function casts(): array
    {
        return [
            'inclusions' => 'array',
            'departure_date' => 'date',
            'return_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function agency(): BelongsTo
    {
        return $this->belongsTo(TravelAgency::class, 'travel_agency_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(TravelPackageMedia::class)->orderBy('position');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TravelBooking::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(TravelPackageInquiry::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(TravelCategory::class, 'travel_package_categories');
    }

    public function destinationCountry(): BelongsTo
    {
        return $this->belongsTo(TravelCountry::class, 'destination_travel_country_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(TravelCity::class, 'destination_travel_city_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function priceFormatted(): string
    {
        return $this->currency . ' ' . number_format($this->price_cents, 2);
    }

    public function seatsRemaining(): ?int
    {
        if ($this->available_seats === null) {
            return null;
        }

        return max(0, $this->available_seats - $this->seats_booked);
    }

    public function coverImage(): ?TravelPackageMedia
    {
        return $this->media->where('media_type', 'image')->first();
    }
}
