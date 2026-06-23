<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public bool $isWishlisted = false;

    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        $countrySetting = $this->whenLoaded('countrySettings', function () {
            return $this->countrySettings->first();
        });

        return [
            'id'               => $this->id,
            'slug'             => $this->slug,
            'name'             => ($countrySetting?->{"name_override_{$lang}"}) ?? $this->{"name_{$lang}"},
            'description'      => $this->{"description_{$lang}"},
            'short_description' => $this->{"short_desc_{$lang}"},
            'model_number'     => $this->model_number,
            'gtin'             => $this->gtin,
            'is_age_restricted' => $this->is_age_restricted,
            'min_age'          => $this->min_age,
            'is_hazardous'     => $this->is_hazardous,
            'has_variants'     => $this->has_variants,
            'rating_avg'       => (float) $this->rating_avg,
            'rating_count'     => (int) $this->rating_count,
            'total_sold'       => (int) $this->total_sold,
            'brand'            => $this->whenLoaded('brand', fn() => [
                'id'   => $this->brand->id,
                'name' => $this->brand->{"name_{$lang}"},
                'slug' => $this->brand->slug,
            ]),
            'category'         => $this->whenLoaded('category', fn() => [
                'id'   => $this->category->id,
                'name' => $this->category->{"name_{$lang}"},
                'slug' => $this->category->slug,
            ]),
            'images'           => $this->whenLoaded('images', fn() =>
                $this->images->map(fn($img) => [
                    'id'             => $img->id,
                    'url'            => \Storage::disk($img->disk)->url($img->path),
                    'alt'            => $img->{"alt_text_{$lang}"},
                    'is_primary'     => $img->is_primary,
                    'position'       => $img->position,
                    'variant_id'     => $img->product_variant_id,
                ])
            ),
            'variants'         => $this->whenLoaded('variants', fn() =>
                $this->variants->filter(fn($v) => $v->is_active)->map(fn($v) => [
                    'id'           => $v->id,
                    'sku'          => $v->sku,
                    'variant_name' => $v->variant_name,
                    'is_default'   => $v->is_default,
                    'position'     => $v->position,
                    'attributes'   => $v->variantAttributes->map(fn($va) => [
                        'attribute_id'   => $va->attribute_id,
                        'attribute_code' => $va->attribute?->code,
                        'attribute_name' => $va->attribute?->{"name_{$lang}"},
                        'value_id'       => $va->attribute_value_id,
                        'value'          => $va->attributeValue?->{"value_{$lang}"}
                                           ?? $va->{"value_text_{$lang}"}
                                           ?? $va->value_number,
                        'color_hex'      => $va->attributeValue?->code_hex,
                    ]),
                ])->values()
            ),
            'sellers'          => $this->whenLoaded('activeListings', fn() =>
                SellerListingResource::collection($this->activeListings)->resolve()
            ),
            'reviews'          => $this->whenLoaded('topReviews', fn() =>
                ReviewResource::collection($this->topReviews)->resolve()
            ),
            'related'          => $this->whenLoaded('related', fn() =>
                ProductListResource::collection($this->related)->resolve()
            ),
            'is_wishlisted'    => $this->isWishlisted,
            'seo'              => [
                'title'       => $countrySetting?->seo_title ?? $this->{"seo_title_{$lang}"},
                'description' => $this->{"seo_description_{$lang}"},
            ],
        ];
    }
}
