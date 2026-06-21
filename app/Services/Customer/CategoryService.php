<?php

namespace App\Services\Customer;

use App\Http\Resources\Customer\CategoryTreeResource;
use App\Models\Category;
use App\Models\Country;
use App\Models\Page;
use App\Services\PageBuilderService;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public function __construct(
        private readonly PageBuilderService $pageBuilder,
    ) {}

    /**
     * Full nested category tree for nav/menu use.
     * Cached 10 min per country, tagged 'categories' so admin publishes can flush it.
     */
    public function getTree(Country $country): array
    {
        return Cache::tags(['categories'])
            ->remember("category_tree:{$country->id}", 600, function () {
                // toTree() builds the hierarchy in PHP from a single lft/rgt-ordered query.
                $nodes = Category::where('is_active', true)
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->toTree();

                return CategoryTreeResource::collection($nodes)->resolve();
            });
    }

    /**
     * IDs of $category plus all active descendants — single query via nested-set lft/rgt range.
     *
     * @return list<string>
     */
    public function getDescendantIds(Category $category): array
    {
        return $category->descendants()
            ->where('is_active', true)
            ->pluck('id')
            ->prepend($category->id)
            ->toArray();
    }

    /**
     * Cached product count for a category + its descendants.
     * Uses the product_count column maintained by RecalculateCategoryStatsJob.
     */
    public function productCount(Category $category, Country $country): int
    {
        return Cache::tags(['categories'])
            ->remember("category_product_count:{$country->id}:{$category->id}", 600, function () use ($category) {
                return (int) $category->product_count;
            });
    }

    /**
     * Resolve page_blocks for a category page, walking up the ancestor chain
     * until a published default page is found for this country.
     * Cached 5 min per (category, country), flushed by PageBuilderService::flushPageCache
     * via the shared 'pages' cache tag.
     *
     * @return list<array<string,mixed>>
     */
    public function resolvePageBlocks(Category $category, Country $country): array
    {
        // Build ancestor chain: [this category, parent, grandparent, ...]
        $chain = collect([$category])
            ->merge(
                $category->ancestors()->orderByDesc('depth')->get()
            );

        foreach ($chain as $node) {
            $cacheKey = "category_page_blocks:{$country->id}:{$node->id}";

            // Wrap result in an array so Cache::remember can safely store a "not found" state
            // without ambiguity around null values.
            $cached = Cache::tags(['pages'])
                ->remember($cacheKey, 300, function () use ($node, $country) {
                    $page = Page::where('page_type', 'category')
                        ->where('reference_id', $node->id)
                        ->where('country_id', $country->id)
                        ->where('status', 'published')
                        ->where('is_default', true)
                        ->first();

                    if (!$page) {
                        return ['found' => false, 'blocks' => []];
                    }

                    return [
                        'found'  => true,
                        'blocks' => $this->pageBuilder->getPageWithBlocks($page->id)['blocks'],
                    ];
                });

            if ($cached['found']) {
                return $cached['blocks'];
            }
        }

        return [];
    }
}
