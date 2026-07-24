<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NawyListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->productVariant->product;
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id' => $this->id,
            'price' => $this->price,
            'currency' => $this->currency,
            'payment_options' => $this->payment_options,
            'fulfillment_type' => $this->fulfillment_type,
            'is_exclusive' => (bool) $this->is_exclusive,
            'rating_avg' => (float) $this->rating_avg,
            'rating_count' => (int) $this->rating_count,
            'product' => [
                'name_en' => $product->name_en,
                'name_ar' => $product->name_ar,
                'slug' => $product->slug,
                'primary_image_url' => $primaryImage?->url,
            ],
            'variant' => [
                'attributes' => $this->productVariant->attributeValues->map(fn ($av) => [
                    'name_en' => $av->attribute?->name_en,
                    'name_ar' => $av->attribute?->name_ar,
                    'value_en' => $av->value_en,
                    'value_ar' => $av->value_ar,
                ])->values()->all(),
            ],
            'category' => $this->nawyCategory ? [
                'id' => $this->nawyCategory->id,
                'name_en' => $this->nawyCategory->name_en,
                'name_ar' => $this->nawyCategory->name_ar,
            ] : null,
            'fulfillment_badge' => $this->fulfillmentBadge($this->fulfillment_type),
        ];
    }

    private function fulfillmentBadge(?string $fulfillmentType): array
    {
        return match ($fulfillmentType) {
            'express' => ['label_en' => 'Express', 'label_ar' => 'إكسبريس', 'color' => 'green'],
            'global' => ['label_en' => 'Global', 'label_ar' => 'عالمي', 'color' => 'blue'],
            'mixed' => ['label_en' => 'Express & Global', 'label_ar' => 'إكسبريس وعالمي', 'color' => 'purple'],
            default => ['label_en' => 'Global', 'label_ar' => 'عالمي', 'color' => 'blue'],
        };
    }
}
