<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassifiedInquiryAsBuyerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'message'        => $this->message,
            'contact_phone'  => $this->contact_phone,
            'status'         => $this->status,
            'created_at'     => $this->created_at?->toIso8601String(),
            'listing'        => $this->whenLoaded('listing', fn () => [
                'id'             => $this->listing->id,
                'listing_number' => $this->listing->listing_number,
                'title_en'       => $this->listing->title_en,
                'title_ar'       => $this->listing->title_ar,
                'status'         => $this->listing->status,
                'primary_image'  => $this->listing->primary_image_url,
            ]),
        ];
    }
}
