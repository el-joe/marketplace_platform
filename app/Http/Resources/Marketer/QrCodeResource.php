<?php

namespace App\Http\Resources\Marketer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QrCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->code_type,
            'label'       => $this->custom_label,
            'target_url'  => $this->barcode_value,
            'image_url'   => $this->qr_url,
            'scan_count'  => $this->scan_count,
            'is_active'   => $this->is_active,
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
