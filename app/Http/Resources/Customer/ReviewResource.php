<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'rating'               => $this->rating,
            'title'                => $this->title,
            'body'                 => $this->body,
            'is_verified_purchase' => $this->is_verified_purchase,
            'helpful_count'        => $this->helpful_count,
            'not_helpful_count'    => $this->not_helpful_count,
            'reviewer_name'        => $this->customer?->name ?? 'Anonymous',
            'created_at'           => $this->created_at?->toIso8601String(),
            'vendor_reply'         => $this->whenLoaded('vendorReply', function () {
                return $this->vendorReply ? [
                    'body'       => $this->vendorReply->body,
                    'created_at' => $this->vendorReply->created_at?->toIso8601String(),
                ] : null;
            }),
        ];
    }
}
