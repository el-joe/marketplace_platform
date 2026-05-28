<?php

namespace App\Services;

use App\Models\AdImageItem;
use App\Models\Admin;
use App\Models\BlockType;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBlockProduct;
use App\Models\PageBlockRevision;
use App\Models\PageRevision;
use App\Models\SliderSlide;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PageBuilderService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Pages
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Page + ordered blocks (with block_type details) as plain arrays.
     */
    public function getPageWithBlocks(string $pageId): array
    {
        $page = Page::with('country')->findOrFail($pageId);

        $blocks = PageBlock::with('blockType')
            ->where('page_id', $pageId)
            ->orderBy('position')
            ->get()
            ->map(fn (PageBlock $b) => $this->serializeBlock($b))
            ->all();

        return [
            'page' => [
                'id' => $page->id,
                'name' => $page->name,
                'slug' => $page->slug,
                'page_type' => $page->page_type,
                'country_id' => $page->country_id,
                'country_code' => optional($page->country)->site_code,
                'country_name' => optional($page->country)->name_en,
                'status' => $page->status,
                'version' => $page->version,
                'published_at' => optional($page->published_at)->toIso8601String(),
            ],
            'blocks' => $blocks,
        ];
    }

    public function createPage(array $data, Admin $admin): Page
    {
        return Page::create([
            'country_id' => $data['country_id'] ?? null,
            'page_type' => $data['page_type'],
            'reference_id' => $data['reference_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'status' => 'draft',
            'version' => 1,
            'is_default' => false,
            'last_edited_by_admin_id' => $admin->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Blocks
    // ─────────────────────────────────────────────────────────────────────────

    public function addBlock(Page $page, string $blockTypeCode, int $position, Admin $admin): PageBlock
    {
        $type = BlockType::where('code', $blockTypeCode)->where('is_active', true)->first();
        if (! $type) {
            throw ValidationException::withMessages(['block_type_code' => 'Unknown or inactive block type.']);
        }

        if ($type->requires_permission && ! $admin->hasPermissionTo($type->requires_permission)) {
            throw ValidationException::withMessages(['block_type_code' => 'You do not have permission to add this block type.']);
        }

        if ($type->max_per_page !== null) {
            $current = PageBlock::where('page_id', $page->id)
                ->where('block_type', $type->code)
                ->count();
            if ($current >= $type->max_per_page) {
                throw ValidationException::withMessages([
                    'block_type_code' => sprintf('Maximum of %d %s block(s) reached on this page.', $type->max_per_page, $type->label_en),
                ]);
            }
        }

        $block = DB::transaction(function () use ($page, $type, $position, $admin) {
            $block = PageBlock::create([
                'page_id' => $page->id,
                'block_type' => $type->code,
                'position' => $position,
                'config' => $type->default_config ?? [],
                'is_visible' => true,
                'device_target' => 'all',
                'audience' => 'all',
                'cache_ttl_seconds' => 300,
                'created_by_admin_id' => $admin->id,
            ]);

            $this->writeBlockRevision($block, $admin, 'created');
            $this->touchPage($page, $admin);

            return $block;
        });

        return $block->load('blockType');
    }

    public function updateBlockConfig(PageBlock $block, array $config, string $changeType, Admin $admin): int
    {
        return DB::transaction(function () use ($block, $config, $changeType, $admin) {
            $block->config = $config;
            $block->updated_by_admin_id = $admin->id;
            $block->save();

            $rev = $this->writeBlockRevision($block, $admin, $changeType ?: 'config_updated');
            $this->touchPage($block->page, $admin);

            return $rev->revision_number;
        });
    }

    public function updateBlockVisibility(PageBlock $block, array $data, Admin $admin): int
    {
        return DB::transaction(function () use ($block, $data, $admin) {
            $block->fill([
                'is_visible' => (bool) ($data['is_visible'] ?? $block->is_visible),
                'visible_from' => $data['visible_from'] ?? null,
                'visible_until' => $data['visible_until'] ?? null,
                'device_target' => $data['device_target'] ?? $block->device_target,
                'audience' => $data['audience'] ?? $block->audience,
                'updated_by_admin_id' => $admin->id,
            ])->save();

            $rev = $this->writeBlockRevision($block, $admin, 'visibility_changed');
            $this->touchPage($block->page, $admin);

            return $rev->revision_number;
        });
    }

    public function removeBlock(PageBlock $block): void
    {
        $block->delete();
    }

    public function reorderBlocks(array $orderedBlocks): void
    {
        DB::transaction(function () use ($orderedBlocks) {
            foreach ($orderedBlocks as $item) {
                if (! isset($item['id'], $item['position'])) {
                    continue;
                }
                PageBlock::whereKey($item['id'])->update(['position' => (int) $item['position']]);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Publish / revisions
    // ─────────────────────────────────────────────────────────────────────────

    public function publishPage(Page $page, Admin $admin, string $reason = ''): void
    {
        DB::transaction(function () use ($page, $admin, $reason) {
            $blocks = PageBlock::with(['blockType', 'slides', 'adImageItems', 'blockProducts', 'blockCategories'])
                ->where('page_id', $page->id)
                ->orderBy('position')
                ->get();

            if ($blocks->isEmpty()) {
                throw ValidationException::withMessages(['page' => 'Cannot publish an empty page. Add at least one block first.']);
            }

            $page->status = 'published';
            $page->published_at = now();
            $page->published_by_admin_id = $admin->id;
            $page->version = (int) $page->version + 1;

            // Demote any other default page for the same (country, type, ref).
            Page::where('id', '!=', $page->id)
                ->where('country_id', $page->country_id)
                ->where('page_type', $page->page_type)
                ->where('reference_id', $page->reference_id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $page->is_default = true;
            $page->save();

            PageRevision::create([
                'page_id' => $page->id,
                'version' => $page->version,
                'blocks_snapshot' => $blocks->map(fn (PageBlock $b) => $this->serializeBlock($b, true))->all(),
                'published_by_admin_id' => $admin->id,
                'publish_reason' => $reason !== '' ? $reason : null,
            ]);

            $this->flushPageCache($page);
        });
    }

    public function restoreRevision(PageRevision $revision, Admin $admin): void
    {
        DB::transaction(function () use ($revision, $admin) {
            $page = $revision->page()->firstOrFail();

            // Soft-delete current blocks.
            PageBlock::where('page_id', $page->id)->delete();

            $snapshot = is_array($revision->blocks_snapshot) ? $revision->blocks_snapshot : [];
            foreach ($snapshot as $idx => $blockData) {
                PageBlock::create([
                    'page_id' => $page->id,
                    'block_type' => $blockData['block_type'] ?? 'text_block',
                    'position' => $blockData['position'] ?? $idx,
                    'config' => $blockData['config'] ?? [],
                    'is_visible' => $blockData['is_visible'] ?? true,
                    'visible_from' => $blockData['visible_from'] ?? null,
                    'visible_until' => $blockData['visible_until'] ?? null,
                    'device_target' => $blockData['device_target'] ?? 'all',
                    'audience' => $blockData['audience'] ?? 'all',
                    'cache_ttl_seconds' => $blockData['cache_ttl_seconds'] ?? 300,
                    'created_by_admin_id' => $admin->id,
                ]);
            }

            $page->version = (int) $page->version + 1;
            $page->last_edited_by_admin_id = $admin->id;
            $page->save();

            PageRevision::create([
                'page_id' => $page->id,
                'version' => $page->version,
                'blocks_snapshot' => $snapshot,
                'published_by_admin_id' => $admin->id,
                'publish_reason' => 'Restored from version ' . $revision->version,
            ]);

            $this->flushPageCache($page);
        });
    }

    public function restoreBlockRevision(PageBlockRevision $revision, Admin $admin): void
    {
        DB::transaction(function () use ($revision, $admin) {
            $block = $revision->pageBlock()->firstOrFail();
            $block->config = $revision->config_snapshot ?? [];
            $block->is_visible = (bool) $revision->is_visible_snapshot;
            $block->position = (int) $revision->position_snapshot;
            $block->updated_by_admin_id = $admin->id;
            $block->save();

            $this->writeBlockRevision($block, $admin, 'config_updated', 'Restored from revision #' . $revision->revision_number);
            $this->touchPage($block->page, $admin);
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────────

    private function writeBlockRevision(PageBlock $block, Admin $admin, string $changeType, ?string $reason = null): PageBlockRevision
    {
        $next = (int) PageBlockRevision::where('page_block_id', $block->id)->max('revision_number') + 1;

        return PageBlockRevision::create([
            'page_block_id' => $block->id,
            'page_id' => $block->page_id,
            'revision_number' => $next,
            'config_snapshot' => $block->config ?? [],
            'is_visible_snapshot' => (bool) $block->is_visible,
            'position_snapshot' => (int) $block->position,
            'changed_by_admin_id' => $admin->id,
            'change_reason' => $reason,
            'change_type' => $changeType,
        ]);
    }

    private function touchPage(?Page $page, Admin $admin): void
    {
        if (! $page) {
            return;
        }
        $page->forceFill(['last_edited_by_admin_id' => $admin->id])->save();
    }

    private function serializeBlock(PageBlock $block, bool $forSnapshot = false): array
    {
        $base = [
            'id' => $block->id,
            'block_type' => $block->block_type,
            'position' => (int) $block->position,
            'config' => $block->config ?? [],
            'is_visible' => (bool) $block->is_visible,
            'visible_from' => optional($block->visible_from)->toIso8601String(),
            'visible_until' => optional($block->visible_until)->toIso8601String(),
            'device_target' => $block->device_target,
            'audience' => $block->audience,
            'cache_ttl_seconds' => (int) $block->cache_ttl_seconds,
        ];

        if (! $forSnapshot) {
            $base['label_en'] = optional($block->blockType)->label_en ?? $block->block_type;
            $base['icon'] = optional($block->blockType)->icon ?? 'cube';
            $base['preview_text'] = $block->getPreviewText();
        }

        return $base;
    }

    private function flushPageCache(Page $page): void
    {
        $countryCode = optional($page->country()->first())->site_code ?? 'all';

        try {
            Cache::tags(['pages', "page:{$countryCode}:{$page->slug}"])->flush();
        } catch (\Throwable $e) {
            // Cache store doesn't support tags (e.g. file driver) — fall back to no-op.
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Slides
    // ─────────────────────────────────────────────────────────────────────────

    public function saveSlide(PageBlock $block, ?string $slideId, array $data): SliderSlide
    {
        $payload = [
            'page_block_id' => $block->id,
            'position' => (int) ($data['position'] ?? SliderSlide::where('page_block_id', $block->id)->max('position') + 1),
            'desktop_file_id' => $data['desktop_file_id'] ?? null,
            'mobile_file_id' => $data['mobile_file_id'] ?? null,
            'title_en' => $data['title_en'] ?? null,
            'title_ar' => $data['title_ar'] ?? null,
            'subtitle_en' => $data['subtitle_en'] ?? null,
            'subtitle_ar' => $data['subtitle_ar'] ?? null,
            'cta_label_en' => $data['cta_label_en'] ?? null,
            'cta_label_ar' => $data['cta_label_ar'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'cta_open_new_tab' => (bool) ($data['cta_open_new_tab'] ?? false),
            'text_color' => $data['text_color'] ?? '#ffffff',
            'text_position' => $data['text_position'] ?? 'left',
            'overlay_opacity' => $data['overlay_opacity'] ?? 0.30,
            'link_type' => $data['link_type'] ?? null,
            'link_reference_id' => $data['link_reference_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'visible_from' => $data['visible_from'] ?? null,
            'visible_until' => $data['visible_until'] ?? null,
        ];

        if ($slideId) {
            $slide = SliderSlide::where('page_block_id', $block->id)->findOrFail($slideId);
            $slide->update($payload);
            return $slide;
        }

        return SliderSlide::create($payload);
    }

    public function reorderSlides(array $ordered): void
    {
        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $row) {
                SliderSlide::whereKey($row['id'])->update(['position' => (int) $row['position']]);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ad images
    // ─────────────────────────────────────────────────────────────────────────

    public function saveAdImage(PageBlock $block, ?string $itemId, array $data): AdImageItem
    {
        $payload = [
            'page_block_id' => $block->id,
            'position' => (int) ($data['position'] ?? AdImageItem::where('page_block_id', $block->id)->max('position') + 1),
            'file_id' => $data['file_id'] ?? null,
            'title_en' => $data['title_en'] ?? null,
            'title_ar' => $data['title_ar'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'link_open_new_tab' => (bool) ($data['link_open_new_tab'] ?? false),
            'alt_text_en' => $data['alt_text_en'] ?? null,
            'alt_text_ar' => $data['alt_text_ar'] ?? null,
            'show_title_overlay' => (bool) ($data['show_title_overlay'] ?? true),
            'aspect_ratio' => $data['aspect_ratio'] ?? '4:3',
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        if ($itemId) {
            $item = AdImageItem::where('page_block_id', $block->id)->findOrFail($itemId);
            $item->update($payload);
            return $item;
        }

        return AdImageItem::create($payload);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Block products
    // ─────────────────────────────────────────────────────────────────────────

    public function addBlockProduct(PageBlock $block, string $productVariantId, Admin $admin): PageBlockProduct
    {
        return PageBlockProduct::firstOrCreate(
            ['page_block_id' => $block->id, 'product_variant_id' => $productVariantId],
            [
                'position' => (int) PageBlockProduct::where('page_block_id', $block->id)->max('position') + 1,
                'added_by_admin_id' => $admin->id,
            ]
        );
    }

    public function reorderBlockProducts(array $ordered): void
    {
        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $row) {
                PageBlockProduct::whereKey($row['id'])->update(['position' => (int) $row['position']]);
            }
        });
    }
}
