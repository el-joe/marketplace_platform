<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelPackageDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $agency = $this->relationLoaded('agency') ? $this->agency : null;

        return [
            'id'                  => $this->id,
            'title_en'            => $this->title_en,
            'title_ar'            => $this->title_ar,
            'description_en'      => $this->description_en,
            'description_ar'      => $this->description_ar,
            'destination_country' => $this->destination_country,
            'destination_city'    => $this->destination_city,
            'price_cents'         => $this->price_cents,
            'currency'            => $this->currency,
            'duration_days'       => $this->duration_days,
            'duration_nights'     => $this->duration_nights,
            'departure_date'      => $this->departure_date?->toDateString(),
            'return_date'         => $this->return_date?->toDateString(),
            'available_seats'     => $this->available_seats,
            'seats_remaining'     => $this->seatsRemaining(),
            'inclusions'          => $this->inclusions ?? [],
            'images'              => $this->relationLoaded('media')
                ? $this->media->map(fn ($m) => [
                    'id'       => $m->id,
                    'url'      => asset('storage/' . $m->file_path),
                    'position' => $m->position,
                ])->values()
                : [],
            'categories'          => $this->relationLoaded('categories')
                ? $this->categories->map(fn ($c) => [
                    'id'   => $c->id,
                    'name' => $c->{'name_' . $lang},
                    'slug' => $c->slug,
                ])->values()
                : [],
            'agency'              => $agency ? [
                'id'             => $agency->id,
                'name'           => $agency->name,
                'logo_url'       => $agency->logoUrl(),
                'license_number' => $agency->license_number,
            ] : null,
            'status'              => $this->status,
        ];
    }
}
