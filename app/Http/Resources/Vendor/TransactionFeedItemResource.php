<?php

namespace App\Http\Resources\Vendor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionFeedItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return match ($this->resource['type']) {
            'sale'   => $this->saleShape(),
            'refund' => $this->refundShape(),
            'payout' => $this->payoutShape(),
            default  => $this->resource,
        };
    }

    private function saleShape(): array
    {
        return [
            'type'             => 'sale',
            'date'             => $this->resource['date'],
            'reference'        => $this->resource['reference'],
            'description'      => $this->resource['description'],
            'gross_cents'      => $this->resource['gross_cents'],
            'commission_cents' => $this->resource['commission_cents'],
            'net_cents'        => $this->resource['net_cents'],
            'payout_status'    => $this->resource['payout_status'],
        ];
    }

    private function refundShape(): array
    {
        return [
            'type'         => 'refund',
            'date'         => $this->resource['date'],
            'reference'    => $this->resource['reference'],
            'description'  => $this->resource['description'],
            'amount_cents' => $this->resource['amount_cents'],
        ];
    }

    private function payoutShape(): array
    {
        return [
            'type'         => 'payout',
            'date'         => $this->resource['date'],
            'reference'    => $this->resource['reference'],
            'description'  => $this->resource['description'],
            'amount_cents' => $this->resource['amount_cents'],
            'currency'     => $this->resource['currency'],
            'receipt_url'  => $this->resource['receipt_url'],
        ];
    }
}
