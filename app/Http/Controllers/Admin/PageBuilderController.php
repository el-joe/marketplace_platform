<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdImageItem;
use App\Models\BlockType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Country;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBlockProduct;
use App\Models\PageBlockRevision;
use App\Models\PageRevision;
use App\Models\ProductVariant;
use App\Models\SliderSlide;
use App\Services\PageBuilderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class PageBuilderController extends Controller
{
    public function __construct(private PageBuilderService $service)
    {
    }

    // ─────────────────────────────────────────────────────────────────────
    // UI
    // ─────────────────────────────────────────────────────────────────────

    public function index()
    {
        $pages = Page::orderByDesc('updated_at')
            ->get(['id', 'name', 'slug', 'page_type', 'country_id', 'status', 'version']);

        $countries = Country::orderBy('name_en')->get(['id', 'name_en', 'site_code']);

        $blockTypes = BlockType::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        return view('admin.page-builder.index', compact('pages', 'countries', 'blockTypes'));
    }

    public function loadPage(Request $request)
    {
        $request->validate(['page_id' => 'required|uuid']);
        return response()->json($this->service->getPageWithBlocks($request->string('page_id')));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Pages
    // ─────────────────────────────────────────────────────────────────────

    public function createPage(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'page_type' => 'required|string|max:30',
            'country_id' => 'required|uuid|exists:countries,id',
            'slug' => 'required|string|max:255',
            'reference_id' => 'nullable|uuid',
        ]);

        $page = $this->service->createPage($data, $this->admin());

        return response()->json(['page' => $page]);
    }

    public function publishPage(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'page_id' => 'required|uuid|exists:pages,id',
            'reason' => 'nullable|string|max:255',
        ]);

        $page = Page::findOrFail($data['page_id']);
        $this->service->publishPage($page, $this->admin(), $data['reason'] ?? '');

        return response()->json(['success' => true, 'page' => $page->fresh()]);
    }

    public function getPageRevisions(string $pageId)
    {
        $revisions = PageRevision::where('page_id', $pageId)
            ->with('publishedByAdmin:id,name')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get(['id', 'version', 'published_by_admin_id', 'publish_reason', 'created_at']);

        return response()->json(['revisions' => $revisions]);
    }

    public function restorePageRevision(string $revisionId)
    {
        $this->authorizeManage();

        $revision = PageRevision::findOrFail($revisionId);
        $this->service->restoreRevision($revision, $this->admin());

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Blocks
    // ─────────────────────────────────────────────────────────────────────

    public function addBlock(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'page_id' => 'required|uuid|exists:pages,id',
            'block_type_code' => 'required|string',
            'position' => 'required|integer|min:0',
        ]);

        $page = Page::findOrFail($data['page_id']);
        $block = $this->service->addBlock($page, $data['block_type_code'], (int) $data['position'], $this->admin());

        return response()->json([
            'block_id' => $block->id,
            'block_type' => $block->block_type,
            'default_config' => $block->config,
            'label_en' => optional($block->blockType)->label_en,
            'icon' => optional($block->blockType)->icon,
            'preview_text' => $block->getPreviewText(),
        ]);
    }

    public function getBlockConfig(PageBlock $block)
    {
        return response()->json([
            'id' => $block->id,
            'block_type' => $block->block_type,
            'config' => $block->config ?? [],
            'is_visible' => (bool) $block->is_visible,
            'visible_from' => optional($block->visible_from)->format('Y-m-d H:i'),
            'visible_until' => optional($block->visible_until)->format('Y-m-d H:i'),
            'device_target' => $block->device_target,
            'audience' => $block->audience,
        ]);
    }

    public function updateBlockConfig(Request $request, PageBlock $block)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'config' => 'required|array',
            'change_type' => 'nullable|string|in:created,config_updated,moved,visibility_changed,deleted',
        ]);

        $revisionNumber = $this->service->updateBlockConfig(
            $block,
            $data['config'],
            $data['change_type'] ?? 'config_updated',
            $this->admin()
        );

        return response()->json([
            'success' => true,
            'revision_number' => $revisionNumber,
            'preview_text' => $block->fresh()->getPreviewText(),
        ]);
    }

    public function updateBlockVisibility(Request $request, PageBlock $block)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'is_visible' => 'required|boolean',
            'visible_from' => 'nullable|date',
            'visible_until' => 'nullable|date|after_or_equal:visible_from',
            'device_target' => 'nullable|in:all,desktop,mobile,app',
            'audience' => 'nullable|in:all,guest,logged_in,vip',
        ]);

        $revisionNumber = $this->service->updateBlockVisibility($block, $data, $this->admin());

        return response()->json(['success' => true, 'revision_number' => $revisionNumber]);
    }

    public function removeBlock(PageBlock $block)
    {
        $this->authorizeManage();
        $this->service->removeBlock($block);
        return response()->json(['success' => true]);
    }

    public function reorderBlocks(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'blocks' => 'required|array|min:1',
            'blocks.*.id' => 'required|uuid',
            'blocks.*.position' => 'required|integer|min:0',
        ]);

        $this->service->reorderBlocks($data['blocks']);
        return response()->json(['success' => true]);
    }

    public function getRevisions(PageBlock $block)
    {
        $revisions = $block->revisions()
            ->with('changedByAdmin:id,name')
            ->limit(50)
            ->get(['id', 'page_block_id', 'revision_number', 'change_type', 'change_reason', 'changed_by_admin_id', 'created_at']);

        return response()->json(['revisions' => $revisions]);
    }

    public function restoreBlockRevision(string $revisionId)
    {
        $this->authorizeManage();
        $revision = PageBlockRevision::findOrFail($revisionId);
        $this->service->restoreBlockRevision($revision, $this->admin());
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Config form partial
    // ─────────────────────────────────────────────────────────────────────

    public function configFormPartial(Request $request)
    {
        $request->validate([
            'block_type_code' => 'required|string',
            'block_id' => 'nullable|uuid',
        ]);

        $blockType = BlockType::where('code', $request->string('block_type_code'))->firstOrFail();
        $block = null;
        $config = (array) ($blockType->default_config ?? []);

        if ($id = $request->string('block_id')->toString()) {
            $block = PageBlock::find($id);
            if ($block) {
                $config = $block->config ?? $config;
            }
        }

        $view = 'admin.page-builder.config-forms.' . str_replace('_', '-', $blockType->code);

        if (!View::exists($view)) {
            $view = 'admin.page-builder.config-forms.generic';
        }

        return response()->view($view, [
            'blockType' => $blockType,
            'block' => $block,
            'config' => $config,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Slides
    // ─────────────────────────────────────────────────────────────────────

    public function getSlides(PageBlock $block)
    {
        $slides = $block->slides()
            ->orderBy('position')
            ->get();

        return response()->json(['slides' => $slides]);
    }

    public function saveSlide(Request $request, PageBlock $block)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'id' => 'nullable|uuid',
            'position' => 'nullable|integer|min:0',
            'desktop_file_id' => 'nullable|integer|exists:files,id',
            'mobile_file_id' => 'nullable|integer|exists:files,id',
            'title_en' => 'nullable|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:500',
            'subtitle_ar' => 'nullable|string|max:500',
            'cta_label_en' => 'nullable|string|max:100',
            'cta_label_ar' => 'nullable|string|max:100',
            'cta_url' => 'nullable|string|max:500',
            'cta_open_new_tab' => 'nullable|boolean',
            'text_color' => 'nullable|string|max:7',
            'text_position' => 'nullable|in:left,center,right',
            'overlay_opacity' => 'nullable|numeric|between:0,1',
            'link_type' => 'nullable|string|max:20',
            'link_reference_id' => 'nullable|uuid',
            'is_active' => 'nullable|boolean',
            'visible_from' => 'nullable|date',
            'visible_until' => 'nullable|date',
        ]);

        $slide = $this->service->saveSlide($block, $data['id'] ?? null, $data);
        return response()->json(['slide' => $slide]);
    }

    public function deleteSlide(SliderSlide $slide)
    {
        $this->authorizeManage();
        $slide->delete();
        return response()->json(['success' => true]);
    }

    public function reorderSlides(Request $request, PageBlock $block)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'slides' => 'required|array',
            'slides.*.id' => 'required|uuid',
            'slides.*.position' => 'required|integer|min:0',
        ]);

        $this->service->reorderSlides($data['slides']);
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Ad images
    // ─────────────────────────────────────────────────────────────────────

    public function getAdImages(PageBlock $block)
    {
        $items = $block->adImageItems()->orderBy('position')->get();
        return response()->json(['items' => $items]);
    }

    public function saveAdImage(Request $request, PageBlock $block)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'id' => 'nullable|uuid',
            'position' => 'nullable|integer|min:0',
            'file_id' => 'nullable|integer|exists:files,id',
            'title_en' => 'nullable|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'link_url' => 'nullable|string|max:500',
            'link_open_new_tab' => 'nullable|boolean',
            'alt_text_en' => 'nullable|string|max:255',
            'alt_text_ar' => 'nullable|string|max:255',
            'show_title_overlay' => 'nullable|boolean',
            'aspect_ratio' => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);

        $item = $this->service->saveAdImage($block, $data['id'] ?? null, $data);
        return response()->json(['item' => $item]);
    }

    public function deleteAdImage(AdImageItem $adImage)
    {
        $this->authorizeManage();
        $adImage->delete();
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Search (for picker selects)
    // ─────────────────────────────────────────────────────────────────────

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = ProductVariant::query()
            ->with('product:id,name_en')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('product', fn($p) => $p->where('name_en', 'like', "%{$q}%"))
                    ->orWhere('sku', 'like', "%{$q}%");
            })
            ->limit(20)
            ->get(['id', 'product_id', 'sku']);

        return response()->json([
            'results' => $rows->map(fn($v) => [
                'id' => $v->id,
                'text' => trim(optional($v->product)->name_en . ' — ' . $v->sku, ' —'),
            ])->values(),
        ]);
    }

    public function searchCategories(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Category::query()
            ->when($q !== '', fn($query) => $query->where('name_en', 'like', "%{$q}%"))
            ->limit(20)
            ->get(['id', 'name_en']);

        return response()->json([
            'results' => $rows->map(fn($c) => ['id' => $c->id, 'text' => $c->name_en])->values(),
        ]);
    }

    public function searchBrands(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Brand::query()
            ->when($q !== '', fn($query) => $query->where('name_en', 'like', "%{$q}%"))
            ->limit(20)
            ->get(['id', 'name_en']);

        return response()->json([
            'results' => $rows->map(fn($b) => ['id' => $b->id, 'text' => $b->name_en])->values(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Block products
    // ─────────────────────────────────────────────────────────────────────

    public function addBlockProduct(Request $request, PageBlock $block)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'product_variant_id' => 'required|uuid|exists:product_variants,id',
        ]);

        $item = $this->service->addBlockProduct($block, $data['product_variant_id'], $this->admin());
        return response()->json(['item' => $item]);
    }

    public function removeBlockProduct(PageBlockProduct $blockProduct)
    {
        $this->authorizeManage();
        $blockProduct->delete();
        return response()->json(['success' => true]);
    }

    public function reorderBlockProducts(Request $request, PageBlock $block)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|uuid',
            'products.*.position' => 'required|integer|min:0',
        ]);

        $this->service->reorderBlockProducts($data['products']);
        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function admin()
    {
        return auth('admin')->user();
    }

    private function authorizeManage(): void
    {
        $admin = $this->admin();
        abort_unless($admin && $admin->hasPermissionTo('pages.manage'), 403, 'You do not have permission to manage pages.');
    }
}
