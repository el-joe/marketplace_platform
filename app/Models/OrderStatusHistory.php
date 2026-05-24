<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $keyType = 'string';
    protected $incrementing = false;

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $fillable = [
        'id',
        'order_id',
        'sub_order_id',
        'from_status',
        'to_status',
        'changed_by_admin_id',
        'reason',
        'metadata',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function changedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'changed_by_admin_id');
    }
}
