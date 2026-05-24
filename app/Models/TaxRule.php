<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRule extends Model
{
    protected $fillable = [
        'name',
        'country_id',
        'region',
        'tax_type',
        'rate_pct',
        'applies_to',
        'category_id',
        'price_includes_tax',
        'effective_from',
        'effective_until',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
