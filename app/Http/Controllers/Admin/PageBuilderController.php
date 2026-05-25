<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdImageItem;
use App\Models\BlockType;
use App\Models\Category;
use App\Models\Country;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBlockProduct;
use App\Models\PageSection;
use App\Models\SliderSlide;
use App\Services\PageBuilderService;
use App\Traits\HasDataTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PageBuilderController extends Controller
{
    use HasDataTable;

    public function __construct(
        private readonly PageBuilderService $pageBuilder
    ) {
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pages: index / datatable
    // ─────────────────────────────────────────────────────────────────────────

    public function index(): View
    {
        return view('admin.page-builder.index', [
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Page Builder'],
            ],
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $columns = [
            ['searchable_columns' => ['pages.name']],
            ['searchable_columns' => ['pages.page_type']],
            ['searchable_columns' => ['pages.slug']],
            [],
            [],
            ['orderable_column' => 'pages.status'],
            ['orderable_column' => 'pages.updated_at'],
        ];

        $query = Page::query()
            ->leftJoin('countries as c', 'c.id', '=', 'pages.country_id')
            ->leftJoin('admins as a', 'a.id', '=', 'pages.last_edited_by_admin_id')
            ->select([
                'pages.id',
                'pages.name',
                'pages.page_type',
                'pages.slug',
                'pages.status',
                'pages.version',
                'pages.is_default',
                'pages.publish_at',
                'pages.published_at',
                'pages.updated_at',
                'c.name_en as country_name',
                'a.name as editor_name',
            ]);

        $query = $this->applyFilters($query, $request, [
            'status' => fn($q, $v) => $q->where('pages.status', $v),
            'page_type' => fn($q, $v) => $q->where('pages.page_type', $v),
            'country_id' => fn($q, $v) => $q->where('pages.country_id', $v),
        ]);

        return $this->dataTableResponse($request, $query, $columns, function ($row) {
            return [
                'id' => $row->id,
                'name' => e($row->name),
                'page_type' => $row->page_type,
                'slug' => e($row->slug),
                'status' => $row->status,
                'version' => $row->version,
                'country_name' => e($row->country_name ?? 'All'),
                'editor_name' => e($row->editor_name ?? '—'),
                'published_at' => $row->published_at,
                'updated_at' => $row->updated_at,
                'show_url' => route('admin.page-builder.edit', $row->id),
            ];
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pages: create / store
    // ─────────────────────────────────────────────────────────────────────────

    public function create(): View
    {
        return view('admin.page-builder.create', [
            'countries' => Country::orderBy('name_en')->get(),
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Page Builder', 'url' => route('admin.page-builder.index')],
                ['label' => 'New Page'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'slug' => 'required|string|max:255',
            'page_type' => 'required|string|in:home,category,brand,landing,campaign,custom',
            'country_id' => 'required|uuid|exists:countries,id',
            'reference_id' => 'nullable|uuid',
            'is_default' => 'boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $admin = auth('admin')->user();
        $page = $this->pageBuilder->createPage($validated, $admin);

        return response()->json([
            'message' => 'Page created.',
            'redirect' => route('admin.page-builder.edit', $page->id),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pages: edit (visual editor)
    // ─────────────────────────────────────────────────────────────────────────

    public function edit(Page $page): View
    {
        $page->load([
            'country',
            'sections',
            'blocks.slides.desktopFile',
            'blocks.slides.mobileFile',
            'blocks.adImageItems.file',
            'blocks.blockProducts.productVariant.product',
            'blocks.blockCategories.category',
            'publishedByAdmin',
            'lastEditedByAdmin',
        ]);

        $blockTypes = BlockType::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        $categories = Category::where('depth', '<=', 1)
            ->orderBy('name_en')
            ->get(['id', 'name_en']);

        return view('admin.page-builder.show', [
            'page' => $page,
            'blockTypes' => $blockTypes,
            'categories' => $categories,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
                ['label' => 'Page Builder', 'url' => route('admin.page-builder.index')],
                ['label' => $page->name],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pages: update meta
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'slug' => 'sometimes|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'og_image_url' => 'nullable|url|max:500',
            'is_default' => 'boolean',
        ]);

        $admin = auth('admin')->user();
        $this->pageBuilder->updatePage($page, $validated, $admin);

        return response()->json(['message' => 'Page updated.']);
    }

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();
        return response()->json([
            'message' => 'Page deleted.',
            'redirect' => route('admin.page-builder.index'),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pages: lifecycle actions
    // ─────────────────────────────────────────────────────────────────────────

    public function publish(Request $request, Page $page): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
            'publish_at' => 'nullable|date',
        ]);

        $admin = auth('admin')->user();
        $action = $request->input('action', 'publish');

        try {
            match ($action) {
                'publish' => $this->pageBuilder->publishPage($page, $admin, $request->input('reason')),
                'schedule' => $this->pageBuilder->schedulePage($page, $admin, $request->input('publish_at')),
                'unpublish' => $this->pageBuilder->unpublishPage($page, $admin),
                'archive' => $this->pageBuilder->archivePage($page, $admin),
                default => throw new \InvalidArgumentException("Unknown action: $action"),
            };
        } catch (\Exception $e) {
            Log::error('PageBuilder publish error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Page status updated.', 'status' => $page->fresh()->status]);
    }

    public function clone(Page $page): JsonResponse
    {
        $admin = auth('admin')->user();
        $clone = $this->pageBuilder->clonePage($page, $admin);

        return response()->json([
            'message' => "Page cloned as \"{$clone->name}\".",
            'redirect' => route('admin.page-builder.edit', $clone->id),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sections
    // ─────────────────────────────────────────────────────────────────────────

    public function sectionStore(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'background_color' => 'nullable|string|max:20',
            'padding_top' => 'nullable|integer|min:0',
            'padding_bottom' => 'nullable|integer|min:0',
            'max_width' => 'nullable|string|max:20',
        ]);

        $section = $this->pageBuilder->addSection($page, $validated);

        return response()->json(['message' => 'Section added.', 'section' => $section]);
    }

    public function sectionUpdate(Request $request, PageSection $section): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'background_color' => 'nullable|string|max:20',
            'padding_top' => 'nullable|integer|min:0',
            'padding_bottom' => 'nullable|integer|min:0',
            'max_width' => 'nullable|string|max:20',
            'is_visible' => 'boolean',
        ]);

        $this->pageBuilder->updateSection($section, $validated);

        return response()->json(['message' => 'Section updated.']);
    }

    public function sectionDestroy(PageSection $section): JsonResponse
    {
        $this->pageBuilder->deleteSection($section);
        return response()->json(['message' => 'Section deleted.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Blocks
    // ─────────────────────────────────────────────────────────────────────────

    public function blockStore(Request $request, Page $page): JsonResponse
    {
        $request->validate([
            'block_type' => 'required|string|max:50',
            'section_id' => 'nullable|uuid|exists:page_sections,id',
        ]);

        $admin = auth('admin')->user();
        $block = $this->pageBuilder->addBlock(
            $page,
            $request->input('block_type'),
            $admin,
            $request->input('section_id')
        );

        $block->load('blockType');

        return response()->json([
            'message' => 'Block added.',
            'block' => $this->serializeBlock($block),
        ]);
    }

    public function blockUpdate(Request $request, PageBlock $block): JsonResponse
    {
        $admin = auth('admin')->user();

        $config = $request->input('config', []);
        $meta = $request->only(['is_visible', 'visible_from', 'visible_until', 'device_target', 'audience', 'cache_ttl_seconds']);

        $this->pageBuilder->updateBlock($block, $config, $meta, $admin);

        return response()->json(['message' => 'Block updated.', 'block' => $this->serializeBlock($block->refresh())]);
    }

    public function blockDestroy(PageBlock $block): JsonResponse
    {
        $this->pageBuilder->deleteBlock($block);
        return response()->json(['message' => 'Block deleted.']);
    }

    public function blocksReorder(Request $request, Page $page): JsonResponse
    {
        $request->validate(['ordered_ids' => 'required|array', 'ordered_ids.*' => 'uuid']);
        $this->pageBuilder->reorderBlocks($page, $request->input('ordered_ids'));
        return response()->json(['message' => 'Order saved.']);
    }

    public function blockToggleVisibility(PageBlock $block): JsonResponse
    {
        $admin = auth('admin')->user();
        $this->pageBuilder->toggleBlockVisibility($block, $admin);
        return response()->json(['message' => 'Visibility toggled.', 'is_visible' => $block->fresh()->is_visible]);
    }

    public function blockRevisions(PageBlock $block): JsonResponse
    {
        $revisions = $block->revisions()
            ->with('changedByAdmin')
            ->limit(20)
            ->get()
            ->map(fn($r) => [
                'revision_number' => $r->revision_number,
                'change_type' => $r->change_type,
                'change_reason' => $r->change_reason,
                'changed_by' => $r->changedByAdmin?->name ?? '—',
                'changed_at' => $r->created_at?->toISOString(),
            ]);

        return response()->json(['revisions' => $revisions]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Slides (hero_slider)
    // ─────────────────────────────────────────────────────────────────────────

    public function slideStore(Request $request, PageBlock $block): JsonResponse
    {
        $validated = $request->validate([
            'desktop_file_id' => 'nullable|uuid|exists:files,id',
            'mobile_file_id' => 'nullable|uuid|exists:files,id',
            'title_en' => 'nullable|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:500',
            'subtitle_ar' => 'nullable|string|max:500',
            'cta_label_en' => 'nullable|string|max:100',
            'cta_label_ar' => 'nullable|string|max:100',
            'cta_url' => 'nullable|string|max:500',
            'cta_open_new_tab' => 'boolean',
            'text_color' => 'nullable|string|max:7',
            'text_position' => 'nullable|in:left,center,right',
            'overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'link_type' => 'nullable|in:product,category,flash_sale,url',
            'link_reference_id' => 'nullable|uuid',
            'is_active' => 'boolean',
            'visible_from' => 'nullable|date',
            'visible_until' => 'nullable|date',
        ]);

        $slide = $this->pageBuilder->addSlide($block, $validated);
        $slide->load(['desktopFile', 'mobileFile']);

        return response()->json(['message' => 'Slide added.', 'slide' => $this->serializeSlide($slide)]);
    }

    public function slideUpdate(Request $request, SliderSlide $slide): JsonResponse
    {
        $validated = $request->validate([
            'desktop_file_id' => 'nullable|uuid|exists:files,id',
            'mobile_file_id' => 'nullable|uuid|exists:files,id',
            'title_en' => 'nullable|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:500',
            'subtitle_ar' => 'nullable|string|max:500',
            'cta_label_en' => 'nullable|string|max:100',
            'cta_label_ar' => 'nullable|string|max:100',
            'cta_url' => 'nullable|string|max:500',
            'cta_open_new_tab' => 'boolean',
            'text_color' => 'nullable|string|max:7',
            'text_position' => 'nullable|in:left,center,right',
            'overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'link_type' => 'nullable|in:product,category,flash_sale,url',
            'link_reference_id' => 'nullable|uuid',
            'is_active' => 'boolean',
        ]);

        $this->pageBuilder->updateSlide($slide, $validated);
        $slide->load(['desktopFile', 'mobileFile']);

        return response()->json(['message' => 'Slide updated.', 'slide' => $this->serializeSlide($slide->refresh())]);
    }

    public function slideDestroy(SliderSlide $slide): JsonResponse
    {
        $this->pageBuilder->deleteSlide($slide);
        return response()->json(['message' => 'Slide deleted.']);
    }

    public function slidesReorder(Request $request, PageBlock $block): JsonResponse
    {
        $request->validate(['ordered_ids' => 'required|array', 'ordered_ids.*' => 'uuid']);
        $this->pageBuilder->reorderSlides($block, $request->input('ordered_ids'));
        return response()->json(['message' => 'Slides reordered.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ad Image Items
    // ─────────────────────────────────────────────────────────────────────────

    public function adImageStore(Request $request, PageBlock $block): JsonResponse
    {
        $validated = $request->validate([
            'file_id' => 'nullable|uuid|exists:files,id',
            'title_en' => 'nullable|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'link_url' => 'nullable|url|max:500',
            'link_open_new_tab' => 'boolean',
            'alt_text_en' => 'nullable|string|max:255',
            'alt_text_ar' => 'nullable|string|max:255',
            'show_title_overlay' => 'boolean',
            'aspect_ratio' => 'nullable|in:1:1,4:3,16:9,2:1,3:4',
            'is_active' => 'boolean',
        ]);

        $item = $this->pageBuilder->addAdImage($block, $validated);
        $item->load('file');

        return response()->json(['message' => 'Ad image added.', 'item' => $this->serializeAdImage($item)]);
    }

    public function adImageUpdate(Request $request, AdImageItem $item): JsonResponse
    {
        $validated = $request->validate([
            'file_id' => 'nullable|uuid|exists:files,id',
            'title_en' => 'nullable|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'link_url' => 'nullable|url|max:500',
            'link_open_new_tab' => 'boolean',
            'alt_text_en' => 'nullable|string|max:255',
            'alt_text_ar' => 'nullable|string|max:255',
            'show_title_overlay' => 'boolean',
            'aspect_ratio' => 'nullable|in:1:1,4:3,16:9,2:1,3:4',
            'is_active' => 'boolean',
        ]);

        $this->pageBuilder->updateAdImage($item, $validated);
        $item->load('file');

        return response()->json(['message' => 'Ad image updated.', 'item' => $this->serializeAdImage($item->refresh())]);
    }

    public function adImageDestroy(AdImageItem $item): JsonResponse
    {
        $this->pageBuilder->deleteAdImage($item);
        return response()->json(['message' => 'Ad image deleted.']);
    }

    public function adImagesReorder(Request $request, PageBlock $block): JsonResponse
    {
        $request->validate(['ordered_ids' => 'required|array', 'ordered_ids.*' => 'uuid']);
        $this->pageBuilder->reorderAdImages($block, $request->input('ordered_ids'));
        return response()->json(['message' => 'Ad images reordered.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Block Products
    // ─────────────────────────────────────────────────────────────────────────

    public function blockProductStore(Request $request, PageBlock $block): JsonResponse
    {
        $request->validate([
            'product_variant_id' => 'required|uuid|exists:product_variants,id',
        ]);

        $admin = auth('admin')->user();
        $bp = $this->pageBuilder->addBlockProduct($block, $request->input('product_variant_id'), $admin);
        $bp->load('productVariant.product');

        return response()->json([
            'message' => 'Product added.',
            'item' => [
                'id' => $bp->id,
                'variant_id' => $bp->product_variant_id,
                'name' => $bp->productVariant?->product?->name_en ?? '—',
                'position' => $bp->position,
            ]
        ]);
    }

    public function blockProductDestroy(PageBlockProduct $blockProduct): JsonResponse
    {
        $this->pageBuilder->removeBlockProduct($blockProduct);
        return response()->json(['message' => 'Product removed.']);
    }

    public function blockProductsReorder(Request $request, PageBlock $block): JsonResponse
    {
        $request->validate(['ordered_ids' => 'required|array', 'ordered_ids.*' => 'uuid']);
        $this->pageBuilder->reorderBlockProducts($block, $request->input('ordered_ids'));
        return response()->json(['message' => 'Products reordered.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Serializers
    // ─────────────────────────────────────────────────────────────────────────

    private function serializeBlock(PageBlock $block): array
    {
        return [
            'id' => $block->id,
            'block_type' => $block->block_type,
            'block_type_label' => $block->blockType?->label_en ?? $block->block_type,
            'position' => $block->position,
            'is_visible' => $block->is_visible,
            'device_target' => $block->device_target,
            'config' => $block->config,
            'slides_count' => $block->slides()->count(),
            'ad_images_count' => $block->adImageItems()->count(),
            'products_count' => $block->blockProducts()->count(),
        ];
    }

    public function serializeSlidePublic(SliderSlide $slide): array
    {
        return $this->serializeSlide($slide);
    }
    public function serializeAdImagePublic(AdImageItem $item): array
    {
        return $this->serializeAdImage($item);
    }

    private function serializeSlide(SliderSlide $slide): array
    {
        return [
            'id' => $slide->id,
            'position' => $slide->position,
            'title_en' => $slide->title_en,
            'title_ar' => $slide->title_ar,
            'cta_label_en' => $slide->cta_label_en,
            'cta_url' => $slide->cta_url,
            'is_active' => $slide->is_active,
            'desktop_image' => $slide->desktopFile?->full_path,
            'mobile_image' => $slide->mobileFile?->full_path,
            'desktop_file_id' => $slide->desktop_file_id,
            'mobile_file_id' => $slide->mobile_file_id,
            'text_color' => $slide->text_color,
            'text_position' => $slide->text_position,
            'overlay_opacity' => $slide->overlay_opacity,
        ];
    }

    private function serializeAdImage(AdImageItem $item): array
    {
        return [
            'id' => $item->id,
            'position' => $item->position,
            'title_en' => $item->title_en,
            'title_ar' => $item->title_ar,
            'link_url' => $item->link_url,
            'alt_text_en' => $item->alt_text_en,
            'is_active' => $item->is_active,
            'aspect_ratio' => $item->aspect_ratio,
            'image_url' => $item->file?->full_path,
            'file_id' => $item->file_id,
        ];
    }
}
