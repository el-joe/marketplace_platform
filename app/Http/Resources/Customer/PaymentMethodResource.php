<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'gateway' => $this->gateway,
            'card_brand' => $this->card_brand,
            'card_last4' => $this->card_last4,
            'card_exp_month' => $this->card_exp_month,
            'card_exp_year' => $this->card_exp_year,
            'is_default' => (bool) $this->is_default,
            'card_display' => $this->card_display,
            'billing_address' => new AddressResource($this->whenLoaded('billingAddress')),
        ];
    }
}
