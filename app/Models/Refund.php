<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function subOrder(): BelongsTo
    {
        return $this->belongsTo(SubOrder::class);
    }

    public function originalTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'original_transaction_id');
    }

    public function refundTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'refund_transaction_id');
    }

    public function initiatedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'initiated_by_customer_id');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id');
    }
}
