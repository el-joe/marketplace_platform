<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Common "category" envelope block for all 3 browse verticals.
 * Wrap with: new BrowseCategoryResource($model, $type)
 */
class BrowseCategoryResource extends JsonResource
{
    public function __construct(mixed $resource, private readonly string $type)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';

        return [
            'type'          => $this->type,
            'id'            => $this->id,
            'name'          => $this->{'name_' . $lang},
            'slug'          => $this->slug,
            'breadcrumbs'   => $this->buildBreadcrumbs($lang),
            'subcategories' => $this->buildSubcategories($lang),
        ];
    }

    private function buildBreadcrumbs(string $lang): array
    {
        $crumbs = [];

        if ($this->type === 'product') {
            foreach ($this->ancestors()->get() as $ancestor) {
                $crumbs[] = ['id' => $ancestor->id, 'name' => $ancestor->{'name_' . $lang}, 'slug' => $ancestor->slug];
            }
        } else {
            $chain = $this->ancestorChain();
            foreach (array_reverse($chain) as $ancestor) {
                $crumbs[] = ['id' => $ancestor->id, 'name' => $ancestor->{'name_' . $lang}, 'slug' => $ancestor->slug];
            }
        }

        $crumbs[] = ['id' => $this->id, 'name' => $this->{'name_' . $lang}, 'slug' => $this->slug];

        return $crumbs;
    }

    private function buildSubcategories(string $lang): array
    {
        if ($this->type === 'product') {
            return $this->children()
                ->where('is_active', true)
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($c) => [
                    'id'   => $c->id,
                    'name' => $c->{'name_' . $lang},
                    'slug' => $c->slug,
                    'type' => 'product',
                ])
                ->toArray();
        }

        return $this->children()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->{'name_' . $lang},
                'slug' => $c->slug,
                'type' => $this->type,
            ])
            ->toArray();
    }

    /** Walk up parent_id chain for adjacency-list models (classified, travel). */
    private function ancestorChain(): array
    {
        $ancestors = [];
        $node = $this->resource;

        while ($node->parent_id) {
            $node = $node->parent()->first();
            if (!$node) {
                break;
            }
            $ancestors[] = $node;
        }

        return $ancestors;
    }
}
