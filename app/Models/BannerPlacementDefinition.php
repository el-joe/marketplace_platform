<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BannerPlacementDefinition extends Model
{
    public function slots(): HasMany
    {
        return $this->hasMany(PaidAdSlot::class, 'placement_definition_id');
    }
}
