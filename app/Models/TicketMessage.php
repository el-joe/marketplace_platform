<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id',
        'sender',
        'message',
        'is_internal_note',
        'is_ai_generated',
        'created_at',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /** Sender can be Admin, Customer, or Vendor */
    public function sender(): MorphTo
    {
        return $this->morphTo('sender');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }
}
