<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'type'                 => $this->type,
            'amount_cents'         => $this->amount_cents,
            'balance_after_cents'  => $this->balance_after_cents,
            'source_type'          => $this->source_type,
            'description'          => $this->description,
            'created_at'           => $this->created_at,
        ];
    }
}
