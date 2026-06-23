<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Category metadata portion of the GET /categories/{slug} response.
 * Does NOT include products or page_blocks — those are assembled separately
 * in CategoryController::show() since they have different caching lifecycles.
 */
class CategoryPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return [
            'id'             => $this->id,
            'name'           => $this->{'name_' . $lang},
            'slug'           => $this->slug,
            'breadcrumbs'    => $this->buildBreadcrumbs($lang),
            'subcategories'  => $this->directChildren($lang),
        ];
    }

    private function buildBreadcrumbs(string $lang): array
    {
        $crumbs = [];

        foreach ($this->ancestors()->get() as $ancestor) {
            $crumbs[] = [
                'id'   => $ancestor->id,
                'name' => $ancestor->{'name_' . $lang},
                'slug' => $ancestor->slug,
            ];
        }

        $crumbs[] = [
            'id'   => $this->id,
            'name' => $this->{'name_' . $lang},
            'slug' => $this->slug,
        ];

        return $crumbs;
    }

    private function directChildren(string $lang): array
    {
        return $this->children()
            ->where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($child) => [
                'id'            => $child->id,
                'name'          => $child->{'name_' . $lang},
                'slug'          => $child->slug,
                'image_url'     => $child->image_url,
                'product_count' => (int) $child->product_count,
            ])
            ->toArray();
    }
}
