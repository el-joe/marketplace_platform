<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'phone',
        'phone_verified_at',
        'password',
        'store_name',
        'store_slug',
        'store_description',
        'business_name',
        'business_type',
        'business_registration_number',
        'tax_id',
        'contact_email',
        'contact_phone',
        'whatsapp_number',
        'business_address_id',
        'commission_rate',
        'default_warehouse_id',
        'payout_schedule',
        'payout_hold_active',
        'payout_hold_reason',
        'store_rating_avg',
        'store_rating_count',
        'total_sales',
        'total_orders',
        'return_rate_pct',
        'cancellation_rate_pct',
        'sla_compliance_pct',
        'strikes_count',
        'country_id',
        'status',
        'last_login_at',
        'last_login_ip',
        'approved_at',
        'approved_by_admin_id',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function businessAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'business_address_id');
    }

    public function defaultWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function strikes(): HasMany
    {
        return $this->hasMany(VendorStrike::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'owner_vendor_id');
    }

    public function listings(): HasMany
    {
        return $this->hasMany(VendorListing::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(VendorBankAccount::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'model');
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
