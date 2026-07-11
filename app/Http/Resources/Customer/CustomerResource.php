<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status?->value,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'total_orders' => $this->total_orders,
            'total_spent' => (float) $this->total_spent,
            'loyalty_points' => (float) $this->loyalty_points,
            'referral_code' => $this->referral_code,
            'email_verified' => $this->email_verified_at !== null,
            'phone_verified' => $this->phone_verified_at !== null,
            'member_since' => $this->created_at->toDateString(),
        ];
    }
}
