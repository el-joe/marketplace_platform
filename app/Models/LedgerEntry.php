<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    /**
     * Polymorphic owner (Vendor or Customer).
     * Uses manual columns: account_holder_type / account_holder_id.
     */
    public function accountHolder(): MorphTo
    {
        return $this->morphTo('account_holder');
    }
}
