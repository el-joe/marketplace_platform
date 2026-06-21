<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DisputeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'dispute_number'   => $this->dispute_number,
            'order_number'     => $this->order?->order_number,
            'reason'           => $this->reason,
            'description'      => $this->description,
            'status'           => $this->status,
            'resolution'       => $this->resolution,
            'resolution_notes' => $this->resolution_notes,
            'compensation'     => $this->compensation_cents !== null ? $this->compensation_cents / 100 : null,
            'resolved_at'      => $this->resolved_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'messages'         => $this->whenLoaded('messages', fn () =>
                $this->messages
                    ->where('is_internal_note', false)
                    ->values()
                    ->map(fn ($m) => [
                        'id'          => $m->id,
                        'sender_role' => $m->sender_role,
                        'message'     => $m->message,
                        'created_at'  => $m->created_at?->toIso8601String(),
                        'attachments' => $m->files->map(fn ($f) => [
                            'url'  => asset('storage/' . $f->path),
                            'name' => $f->original_name ?? null,
                        ]),
                    ])
            ),
        ];
    }
}
