<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCountrySetting;
use App\Models\ProductImage;
use App\Models\VendorListing;
use App\Services\ProductService;
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

    public function __construct(private readonly ProductService $productService)
    {
    }

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

        $query = Product::query()
            ->leftJoin('categories as c', 'c.id', '=', 'products.category_id')
            ->leftJoin('brands as b', 'b.id', '=', 'products.brand_id')
            ->whereNull('products.deleted_at')
            ->select([
                'products.id',
                'products.name_en',
                'products.name_ar',
                'products.status',
                'products.is_featured',
                'products.rating_avg',
                'products.total_sold',
                'products.created_at',
                'c.name_en as category_name',
                'b.name_en as brand_name',
            ])
            ->addSelect([
                'primary_image' => ProductImage::select('path')
                    ->whereColumn('product_id', 'products.id')
                    ->where('is_primary', true)
                    ->orderBy('position')
                    ->limit(1),
                'seller_count' => VendorListing::selectRaw('COUNT(*)')
                    ->join('product_variants', 'product_variants.id', '=', 'vendor_listings.product_variant_id')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('vendor_listings.status', 'active')
                    ->whereNull('vendor_listings.deleted_at'),
            ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('products.status', $v),
            'category_id' => fn($q, $v) => $q->where('products.category_id', $v),
            'brand_id' => fn($q, $v) => $q->where('products.brand_id', $v),
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
        // try {
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
            'seo_title_en' => $request->seo_title ?: null,
            'seo_description_en' => $request->seo_description ?: null,
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
                'position' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $this->syncVariants($id, $request->input('variants', []));
        }

        // Country settings for all launched countries
        $this->syncCountrySettings($id, $request->input('countries', []));

        // Attach uploaded images to this product
        $this->syncImages($id, $request->input('images', []));

        DB::commit();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('admin.products.index'),
            ]);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');

        // } catch (\Throwable $e) {
        //     DB::rollBack();
        //     Log::error('Product creation failed', ['error' => $e->getMessage()]);

        //     if ($request->expectsJson()) {
        //         return response()->json(['message' => 'Failed to create product.'], 500);
        //     }

        //     return back()->withInput()->withErrors(['error' => 'Failed to create product. Please try again.']);
        // }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Edit / Update
    // ──────────────────────────────────────────────────────────────────────────

    public function edit(string $product): View
    {
        $productData = DB::table('products')->where('id', $product)->whereNull('deleted_at')->firstOrFail();

        // Alias bilingual SEO columns to the names the form expects
        $productData->seo_title = $productData->seo_title_en ?? null;
        $productData->seo_description = $productData->seo_description_en ?? null;

        $variants = DB::table('product_variants')
            ->where('product_id', $product)
            ->whereNull('deleted_at')
            ->orderBy('position')
            ->get();

        $images = DB::table('product_images as pi')
            ->where('pi.product_id', $product)
            ->orderBy('pi.position')
            ->select('pi.id', 'pi.path', 'pi.is_primary', 'pi.alt_text_en', 'pi.size_bytes', 'pi.mime_type')
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
                ->where('a.is_variant_attribute', true)
                ->select('a.id', 'a.name_en')
                ->orderBy('a.sort_order')
                ->get();
        }

        $existingAttrValueIds = DB::table('product_variant_attributes')
            ->whereIn('product_variant_id', $variants->pluck('id'))
            ->pluck('attribute_value_id')
            ->unique()
            ->values()
            ->toArray();

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
        // try {
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
            'seo_title_en' => $request->seo_title ?: null,
            'seo_description_en' => $request->seo_description ?: null,
            'updated_at' => now(),
        ];

        if ($request->filled('slug')) {
            $data['slug'] = $request->slug;
        }

        DB::table('products')->where('id', $product)->update($data);

        if ($request->boolean('has_variants') && $request->has('variants')) {
            $this->syncVariants($product, $request->input('variants', []), update: true);
        }

        if ($request->has('countries')) {
            $this->syncCountrySettings($product, $request->input('countries', []), update: true);
        }

        $this->syncImages($product, $request->input('images', []));

        DB::commit();

        return response()->json(['success' => true, 'message' => 'Product updated successfully.']);

        // } catch (\Throwable $e) {
        //     DB::rollBack();
        //     Log::error('Product update failed', ['id' => $product, 'error' => $e->getMessage()]);
        //     return response()->json(['message' => 'Failed to update product.'], 500);
        // }
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
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'image|max:5120', // 5 MB per image
        ]);

        $images = $request->file('images');
        $ids = [];

        foreach ($images as $file) {
            $path = $file->store('products/images', 'public');

            $imageId = (string) Str::uuid();

            DB::table('product_images')->insert([
                'id' => $imageId,
                'product_id' => null, // assigned when the product form is saved
                'path' => $path,
                'disk' => 'public',
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'position' => 0,
                'is_primary' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $ids[] = $imageId;
        }


        return response()->json([
            'ids' => $ids,
            'urls' => array_map(fn($id) => Storage::url(DB::table('product_images')->where('id', $id)->value('path')), $ids),
            'filenames' => array_map(fn($id) => DB::table('product_images')->where('id', $id)->value('path'), $ids),
        ]);
    }

    public function deleteImage(string $mediaId): JsonResponse
    {
        $image = DB::table('product_images')->where('id', $mediaId)->first();

        if (!$image) {
            return response()->json(['message' => 'Image not found.'], 404);
        }

        Storage::disk($image->disk ?? 'public')->delete($image->path);

        DB::table('product_images')->where('id', $mediaId)->delete();

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
    // GTIN check (spec alias, supports 8/12/13/14 digits)
    // ──────────────────────────────────────────────────────────────────────────

    public function checkGtin(Request $request): JsonResponse
    {
        $gtin = trim($request->input('gtin', ''));
        $exceptId = $request->input('except_id');

        if (!preg_match('/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $gtin)) {
            return response()->json(['data' => ['exists' => false]]);
        }

        $product = $this->productService->checkGtinDuplicate($gtin, $exceptId);

        if (!$product) {
            return response()->json(['data' => ['exists' => false]]);
        }

        return response()->json([
            'data' => [
                'exists' => true,
                'product' => [
                    'id' => $product->id,
                    'name_en' => $product->name_en,
                    'edit_url' => route('admin.products.edit', $product->id),
                ],
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Image reordering
    // ──────────────────────────────────────────────────────────────────────────

    public function reorderImages(Request $request, string $product): JsonResponse
    {
        $request->validate(['ordered_ids' => ['required', 'array'], 'ordered_ids.*' => ['string']]);

        $model = Product::findOrFail($product);
        $this->productService->reorderImages($model, $request->input('ordered_ids'));

        return response()->json(['success' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Country settings
    // ──────────────────────────────────────────────────────────────────────────

    public function countrySettings(string $product): JsonResponse
    {
        $settings = ProductCountrySetting::where('product_id', $product)
            ->with('country:id,name_en,name_ar,iso_code')
            ->get();

        return response()->json(['data' => $settings]);
    }

    public function updateCountrySetting(Request $request, string $setting): JsonResponse
    {
        $row = ProductCountrySetting::findOrFail($setting);

        $validated = $request->validate([
            'field' => ['required', 'string', 'in:is_available,name_override_en,name_override_ar,requires_local_cert,seo_title'],
            'value' => ['present'],
        ]);

        $row->update([$validated['field'] => $validated['value']]);

        return response()->json(['success' => true, 'data' => $row->fresh()]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function formData(): array
    {
        $data = [
            'categories' => DB::table('categories')
                ->where('is_active', true)
                ->orderBy('name_en')
                ->pluck('name_en', 'id'),

            'brands' => DB::table('brands')
                ->where('is_active', true)
                ->orderBy('name_en')
                ->pluck('name_en', 'id'),

            'countries' => DB::table('countries')
                ->where('is_active', true)
                ->where('is_launched', true)
                ->orderBy('name_en')
                ->get(),

            'categoryAttributes' => [],
        ];

        return $data;
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
                'position' => $i,
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
                        'name_override_en' => $setting['name_override_en'] ?? null ?: null,
                        'requires_local_cert' => (bool) ($setting['requires_local_cert'] ?? false),
                        'updated_at' => now(),
                    ]);
            } elseif (!$exists) {
                DB::table('product_country_settings')->insert([
                    'id' => (string) Str::uuid(),
                    'product_id' => $productId,
                    'country_id' => $country->id,
                    'is_available' => (bool) ($setting['is_available'] ?? true),
                    'name_override_en' => $setting['name_override_en'] ?? null ?: null,
                    'requires_local_cert' => (bool) ($setting['requires_local_cert'] ?? false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function syncImages(string $productId, array $imageIds): void
    {
        // Remove images belonging to this product that the user deleted from FilePond
        $removed = DB::table('product_images')
            ->where('product_id', $productId)
            ->when(!empty($imageIds), fn($q) => $q->whereNotIn('id', $imageIds))
            ->get(['id', 'path', 'disk']);

        foreach ($removed as $img) {
            Storage::disk($img->disk ?? 'public')->delete($img->path);
            DB::table('product_images')->where('id', $img->id)->delete();
        }

        // Assign product_id, position, and is_primary for each submitted image ID
        // (covers both newly uploaded images with product_id = null and existing ones)
        foreach ($imageIds as $i => $imageId) {
            DB::table('product_images')
                ->where('id', $imageId)
                ->update([
                    'product_id' => $productId,
                    'position' => $i,
                    'is_primary' => $i === 0,
                    'updated_at' => now(),
                ]);
        }
    }

    private function columnDefinitions(): array
    {
        return [
            ['title' => '', 'data' => 'image', 'name' => 'image', 'orderable' => false, 'searchable' => false, 'className' => 'w-12 px-2'],
            ['title' => 'Name', 'data' => 'name_en', 'name' => 'name_en', 'searchable_columns' => ['products.name_en', 'products.name_ar'], 'orderable_column' => 'products.name_en'],
            ['title' => 'Category', 'data' => 'category', 'name' => 'category', 'orderable_column' => 'c.name_en', 'searchable' => false],
            ['title' => 'Brand', 'data' => 'brand', 'name' => 'brand', 'orderable_column' => 'b.name', 'searchable' => false],
            ['title' => 'Status', 'data' => 'status', 'name' => 'status', 'orderable_column' => 'products.status', 'searchable' => false],
            ['title' => 'Sellers', 'data' => 'seller_count', 'name' => 'seller_count', 'orderable' => false, 'searchable' => false, 'className' => 'text-right'],
            ['title' => 'Rating', 'data' => 'rating_avg', 'name' => 'rating_avg', 'orderable_column' => 'products.rating_avg', 'searchable' => false, 'className' => 'text-right'],
            ['title' => 'Sold', 'data' => 'total_sold', 'name' => 'total_sold', 'orderable_column' => 'products.total_sold', 'searchable' => false, 'className' => 'text-right'],
            ['title' => 'Created', 'data' => 'created_at', 'name' => 'created_at', 'orderable_column' => 'products.created_at', 'searchable' => false],
            ['title' => '', 'data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false, 'className' => 'text-right'],
        ];
    }
}
