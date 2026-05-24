<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    use HasDataTable;

    // ──────────────────────────────────────────────────────────────────────────
    // Index / Listing
    // ──────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        $categories = DB::table('categories')
            ->where('is_active', true)
            ->orderBy('name_en')
            ->pluck('name_en', 'id');

        $brands = DB::table('brands')
            ->where('is_active', true)
            ->orderBy('name_en')
            ->pluck('name_en', 'id');

        return view('admin.products.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Products'],
            ],
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = $this->columnDefinitions();

        $query = DB::table('products as p')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->whereNull('p.deleted_at')
            ->select([
                'p.id',
                'p.name_en',
                'p.name_ar',
                'p.status',
                'p.is_featured',
                'p.rating_avg',
                'p.total_sold',
                'p.created_at',
                'c.name_en as category_name',
                'b.name as brand_name',
                DB::raw("(SELECT f.path FROM product_images pi
                          JOIN files f ON f.id = pi.file_id
                          WHERE pi.product_id = p.id AND pi.is_primary = 1
                          LIMIT 1) as primary_image"),
                DB::raw("(SELECT COUNT(*) FROM seller_listings sl
                          WHERE sl.product_id = p.id AND sl.status = 'active'
                          AND sl.deleted_at IS NULL) as seller_count"),
            ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('p.status', $v),
            'category_id' => fn($q, $v) => $q->where('p.category_id', $v),
            'brand_id' => fn($q, $v) => $q->where('p.brand_id', $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                'id' => $row->id,
                'image' => $row->primary_image ? Storage::url($row->primary_image) : null,
                'name_en' => e($row->name_en),
                'name_ar' => e($row->name_ar ?? ''),
                'category' => e($row->category_name ?? '—'),
                'brand' => e($row->brand_name ?? '—'),
                'status' => $row->status,
                'is_featured' => (bool) $row->is_featured,
                'seller_count' => (int) $row->seller_count,
                'rating_avg' => $row->rating_avg ? number_format((float) $row->rating_avg, 1) : '—',
                'total_sold' => (int) $row->total_sold,
                'created_at' => $row->created_at,
                'edit_url' => route('admin.products.edit', $row->id),
                'delete_url' => route('admin.products.destroy', $row->id),
            ];
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Create / Store
    // ──────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('admin.products.create', $this->formData());
    }

    public function store(StoreProductRequest $request): JsonResponse|RedirectResponse
    {
        DB::beginTransaction();
        try {
            $id = (string) Str::uuid();
            $slug = $request->slug ?: Str::slug($request->name_en) . '-' . Str::lower(Str::random(5));

            DB::table('products')->insert([
                'id' => $id,
                'name_en' => $request->name_en,
                'name_ar' => $request->name_ar,
                'slug' => $slug,
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id ?: null,
                'gtin' => $request->gtin ?: null,
                'model_number' => $request->model_number ?: null,
                'description_en' => $request->description_en ?: null,
                'description_ar' => $request->description_ar ?: null,
                'short_desc_en' => $request->short_desc_en ?: null,
                'short_desc_ar' => $request->short_desc_ar ?: null,
                'status' => $request->status ?? 'draft',
                'has_variants' => $request->boolean('has_variants'),
                'is_featured' => $request->boolean('is_featured'),
                'requires_brand_auth' => $request->boolean('requires_brand_auth'),
                'is_age_restricted' => $request->boolean('is_age_restricted'),
                'min_age' => $request->min_age ?: null,
                'is_hazardous' => $request->boolean('is_hazardous'),
                'seo_title' => $request->seo_title ?: null,
                'seo_description' => $request->seo_description ?: null,
                'rating_avg' => null,
                'total_sold' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Default variant when product has no variants
            if (!$request->boolean('has_variants')) {
                DB::table('product_variants')->insert([
                    'id' => (string) Str::uuid(),
                    'product_id' => $id,
                    'sku' => 'SKU-' . strtoupper(Str::random(8)),
                    'is_default' => true,
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $this->syncVariants($id, $request->input('variants', []));
            }

            // Country settings for all launched countries
            $this->syncCountrySettings($id, $request->input('countries', []));

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect' => route('admin.products.index'),
                ]);
            }

            return redirect()->route('admin.products.index')
                ->with('success', 'Product created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product creation failed', ['error' => $e->getMessage()]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Failed to create product.'], 500);
            }

            return back()->withInput()->withErrors(['error' => 'Failed to create product. Please try again.']);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Edit / Update
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(string $product): View
    {
        $productData = DB::table('products')->where('id', $product)->whereNull('deleted_at')->firstOrFail();

        $variants = DB::table('product_variants')
            ->where('product_id', $product)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->get();

        $images = DB::table('product_images as pi')
            ->join('files as f', 'f.id', '=', 'pi.file_id')
            ->where('pi.product_id', $product)
            ->orderBy('pi.sort_order')
            ->select('pi.id', 'pi.file_id', 'f.path', 'pi.is_primary', 'pi.alt_text')
            ->get();

        $countrySettings = DB::table('product_country_settings')
            ->where('product_id', $product)
            ->get()
            ->keyBy('country_id');

        $categoryAttributes = [];
        if ($productData->category_id) {
            $categoryAttributes = DB::table('category_attributes as ca')
                ->join('attributes as a', 'a.id', '=', 'ca.attribute_id')
                ->where('ca.category_id', $productData->category_id)
                ->where('a.is_variant_type', true)
                ->select('a.id', 'a.name_en')
                ->orderBy('a.sort_order')
                ->get();
        }

        $existingAttrValueIds = DB::table('product_variant_attributes')
            ->whereIn('variant_id', $variants->pluck('id'))
            ->pluck('attribute_value_id')
            ->unique();

        return view('admin.products.edit', array_merge($this->formData(), [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Products', 'url' => route('admin.products.index')],
                ['label' => e($productData->name_en)],
            ],
            'product' => $productData,
            'variants' => $variants,
            'images' => $images,
            'countrySettings' => $countrySettings,
            'categoryAttributes' => $categoryAttributes,
            'existingAttrValues' => $existingAttrValueIds,
        ]));
    }

    public function update(UpdateProductRequest $request, string $product): JsonResponse
    {
        DB::table('products')->where('id', $product)->whereNull('deleted_at')->firstOrFail();

        DB::beginTransaction();
        try {
            $data = [
                'name_en' => $request->name_en,
                'name_ar' => $request->name_ar,
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id ?: null,
                'gtin' => $request->gtin ?: null,
                'model_number' => $request->model_number ?: null,
                'description_en' => $request->description_en ?: null,
                'description_ar' => $request->description_ar ?: null,
                'short_desc_en' => $request->short_desc_en ?: null,
                'short_desc_ar' => $request->short_desc_ar ?: null,
                'status' => $request->status,
                'has_variants' => $request->boolean('has_variants'),
                'is_featured' => $request->boolean('is_featured'),
                'requires_brand_auth' => $request->boolean('requires_brand_auth'),
                'is_age_restricted' => $request->boolean('is_age_restricted'),
                'min_age' => $request->min_age ?: null,
                'is_hazardous' => $request->boolean('is_hazardous'),
                'seo_title' => $request->seo_title ?: null,
                'seo_description' => $request->seo_description ?: null,
                'updated_at' => now(),
            ];

            if ($request->filled('slug')) {
                $data['slug'] = $request->slug;
            }

            DB::table('products')->where('id', $product)->update($data);

            if ($request->has('variants')) {
                $this->syncVariants($product, $request->input('variants', []), update: true);
            }

            if ($request->has('countries')) {
                $this->syncCountrySettings($product, $request->input('countries', []), update: true);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Product updated successfully.']);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product update failed', ['id' => $product, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update product.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Destroy / Bulk
    // ──────────────────────────────────────────────────────────────────────────

    public function destroy(string $product): JsonResponse
    {
        $activeSellers = DB::table('seller_listings')
            ->where('product_id', $product)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count();

        if ($activeSellers > 0) {
            return response()->json([
                'message' => "Cannot delete: {$activeSellers} active seller listing(s) reference this product.",
            ], 422);
        }

        DB::table('products')
            ->where('id', $product)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Product deleted.']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:delete,publish,archive,feature',
            'ids' => 'required|array|min:1',
            'ids.*' => 'string',
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        switch ($action) {
            case 'delete':
                DB::table('products')->whereIn('id', $ids)->update(['deleted_at' => now(), 'updated_at' => now()]);
                $message = count($ids) . ' product(s) deleted.';
                break;
            case 'publish':
                DB::table('products')->whereIn('id', $ids)->update(['status' => 'active', 'updated_at' => now()]);
                $message = count($ids) . ' product(s) published.';
                break;
            case 'archive':
                DB::table('products')->whereIn('id', $ids)->update(['status' => 'discontinued', 'updated_at' => now()]);
                $message = count($ids) . ' product(s) archived.';
                break;
            case 'feature':
                DB::table('products')->whereIn('id', $ids)->update(['is_featured' => true, 'updated_at' => now()]);
                $message = count($ids) . ' product(s) featured.';
                break;
            default:
                return response()->json(['message' => 'Unknown action.'], 422);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Variant generation
    // ──────────────────────────────────────────────────────────────────────────

    public function generateVariants(Request $request): JsonResponse
    {
        $request->validate(['attribute_ids' => 'required|array|min:1']);

        $grouped = DB::table('attributes as a')
            ->join('attribute_values as av', 'av.attribute_id', '=', 'a.id')
            ->whereIn('a.id', $request->input('attribute_ids'))
            ->select('a.id as attr_id', 'a.name_en as attr_name', 'av.id as value_id', 'av.value_en as value_name')
            ->orderBy('a.sort_order')
            ->orderBy('av.sort_order')
            ->get()
            ->groupBy('attr_id');

        if ($grouped->isEmpty()) {
            return response()->json(['data' => []]);
        }

        // Cartesian product
        $combinations = [[]];
        foreach ($grouped as $values) {
            $next = [];
            foreach ($combinations as $combo) {
                foreach ($values as $v) {
                    $next[] = array_merge($combo, [
                        [
                            'attr_id' => $v->attr_id,
                            'attr_name' => $v->attr_name,
                            'value_id' => $v->value_id,
                            'value_name' => $v->value_name,
                        ]
                    ]);
                }
            }
            $combinations = $next;
        }

        $variants = [];
        foreach ($combinations as $i => $combo) {
            $variants[] = [
                'index' => $i,
                'name' => collect($combo)->pluck('value_name')->join(' / '),
                'attributes' => $combo,
                'sku' => '',
                'barcode' => '',
                'weight_grams' => '',
                'is_default' => $i === 0,
                'is_active' => true,
            ];
        }

        return response()->json(['data' => $variants]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Image management
    // ──────────────────────────────────────────────────────────────────────────

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|image|max:5120']); // 5 MB

        $file = $request->file('file');
        $hash = hash_file('sha256', $file->getRealPath());

        // Dedup by content hash
        $existing = DB::table('file_hashes')->where('hash', $hash)->first();
        if ($existing) {
            return response()->json([
                'id' => $existing->file_id,
                'url' => Storage::url($existing->path),
                'filename' => $existing->filename,
            ]);
        }

        $path = $file->store('products/images', 'public');
        $fileId = (string) Str::uuid();

        DB::table('files')->insert([
            'id' => $fileId,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('file_hashes')->insert([
            'id' => (string) Str::uuid(),
            'file_id' => $fileId,
            'hash' => $hash,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'created_at' => now(),
        ]);

        return response()->json([
            'id' => $fileId,
            'url' => Storage::url($path),
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    public function deleteImage(string $mediaId): JsonResponse
    {
        $file = DB::table('files')->where('id', $mediaId)->first();

        if (!$file) {
            return response()->json(['message' => 'Image not found.'], 404);
        }

        Storage::disk('public')->delete($file->path);

        DB::table('product_images')->where('file_id', $mediaId)->delete();
        DB::table('file_hashes')->where('file_id', $mediaId)->delete();
        DB::table('files')->where('id', $mediaId)->delete();

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GTIN duplicate check
    // ──────────────────────────────────────────────────────────────────────────

    public function checkDuplicate(Request $request): JsonResponse
    {
        $gtin = trim($request->input('gtin', ''));

        if (strlen($gtin) !== 13) {
            return response()->json(['data' => ['exists' => false]]);
        }

        $product = DB::table('products')
            ->where('gtin', $gtin)
            ->whereNull('deleted_at')
            ->select('id', 'name_en', 'status')
            ->first();

        if (!$product) {
            return response()->json(['data' => ['exists' => false]]);
        }

        return response()->json([
            'data' => [
                'exists' => true,
                'product' => [
                    'id' => $product->id,
                    'name_en' => $product->name_en,
                    'status' => $product->status,
                    'url' => route('admin.products.edit', $product->id),
                ],
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function formData(): array
    {
        return [
            'categories' => DB::table('categories')
                ->where('is_active', true)
                ->orderBy('name_en')
                ->pluck('name_en', 'id'),

            'brands' => DB::table('brands')
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name', 'id'),

            'countries' => DB::table('countries')
                ->where('is_launched', true)
                ->orderBy('name_en')
                ->get(),

            'categoryAttributes' => [],
        ];
    }

    private function syncVariants(string $productId, array $variants, bool $update = false): void
    {
        if ($update) {
            // Soft-delete existing variants not in the new list
            DB::table('product_variants')
                ->where('product_id', $productId)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        }

        foreach ($variants as $i => $v) {
            DB::table('product_variants')->insert([
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'sku' => $v['sku'] ?: 'SKU-' . strtoupper(Str::random(8)),
                'barcode' => $v['barcode'] ?: null,
                'weight_grams' => isset($v['weight_grams']) && $v['weight_grams'] !== '' ? (int) $v['weight_grams'] : null,
                'is_default' => isset($v['is_default']) && (bool) $v['is_default'],
                'is_active' => !isset($v['is_active']) || (bool) $v['is_active'],
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function syncCountrySettings(string $productId, array $countriesInput, bool $update = false): void
    {
        $countries = DB::table('countries')->where('is_launched', true)->get('id');

        foreach ($countries as $country) {
            $setting = $countriesInput[$country->id] ?? [];

            $exists = DB::table('product_country_settings')
                ->where('product_id', $productId)
                ->where('country_id', $country->id)
                ->exists();

            if ($exists && $update) {
                DB::table('product_country_settings')
                    ->where('product_id', $productId)
                    ->where('country_id', $country->id)
                    ->update([
                        'is_available' => (bool) ($setting['is_available'] ?? true),
                        'name_override' => $setting['name_override'] ?: null,
                        'requires_cert' => (bool) ($setting['requires_cert'] ?? false),
                        'updated_at' => now(),
                    ]);
            } elseif (!$exists) {
                DB::table('product_country_settings')->insert([
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'country_id' => $country->id,
                    'is_available' => (bool) ($setting['is_available'] ?? true),
                    'name_override' => $setting['name_override'] ?: null,
                    'requires_cert' => (bool) ($setting['requires_cert'] ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function columnDefinitions(): array
    {
        return [
            ['title' => '', 'data' => 'image', 'name' => 'image', 'orderable' => false, 'searchable' => false, 'className' => 'w-12 px-2'],
            ['title' => 'Name', 'data' => 'name_en', 'name' => 'name_en', 'searchable_columns' => ['p.name_en', 'p.name_ar'], 'orderable_column' => 'p.name_en'],
            ['title' => 'Category', 'data' => 'category', 'name' => 'category', 'orderable_column' => 'c.name_en', 'searchable' => false],
            ['title' => 'Brand', 'data' => 'brand', 'name' => 'brand', 'orderable_column' => 'b.name', 'searchable' => false],
            ['title' => 'Status', 'data' => 'status', 'name' => 'status', 'orderable_column' => 'p.status', 'searchable' => false],
            ['title' => 'Sellers', 'data' => 'seller_count', 'name' => 'seller_count', 'orderable' => false, 'searchable' => false, 'className' => 'text-right'],
            ['title' => 'Rating', 'data' => 'rating_avg', 'name' => 'rating_avg', 'orderable_column' => 'p.rating_avg', 'searchable' => false, 'className' => 'text-right'],
            ['title' => 'Sold', 'data' => 'total_sold', 'name' => 'total_sold', 'orderable_column' => 'p.total_sold', 'searchable' => false, 'className' => 'text-right'],
            ['title' => 'Created', 'data' => 'created_at', 'name' => 'created_at', 'orderable_column' => 'p.created_at', 'searchable' => false],
            ['title' => '', 'data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false, 'className' => 'text-right'],
        ];
    }
}
