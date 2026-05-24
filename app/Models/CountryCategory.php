<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryCategory extends Model
{
    protected $table = 'country_categories';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'country_id',
        'category_id',
        'is_available',
        'commission_rate',
        'unavailable_reason',
        'notes',
        'updated_by_admin_id',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'commission_rate' => 'decimal:2',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function updatedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by_admin_id');
    }
}
