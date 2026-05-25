<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdImageItem;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBlockProduct;
use App\Models\PageBlockRevision;
use App\Models\PageRevision;
use App\Models\PageSection;
use App\Models\SliderSlide;
use Illuminate\Support\Facades\DB;

class PageBuilderService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Page lifecycle
    // ─────────────────────────────────────────────────────────────────────────

    public function createPage(array $data, Admin $admin): Page
    {
        return Page::create(array_merge($data, [
            'status' => 'draft',
            'version' => 1,
            'last_edited_by_admin_id' => $admin->id,
        ]));
    }

    public function updatePage(Page $page, array $data, Admin $admin): void
    {
        $page->update(array_merge($data, [
            'last_edited_by_admin_id' => $admin->id,
        ]));
    }

    public function publishPage(Page $page, Admin $admin, ?string $reason = null): void
    {
        DB::transaction(function () use ($page, $admin, $reason) {
            $version = $page->version + 1;

            $this->createPageRevision($page, $admin, $reason);

            $page->update([
                'status' => 'published',
                'published_at' => now(),
                'version' => $version,
                'published_by_admin_id' => $admin->id,
                'last_edited_by_admin_id' => $admin->id,
            ]);
        });
    }

    public function schedulePage(Page $page, Admin $admin, string $publishAt): void
    {
        $page->update([
            'status' => 'scheduled',
            'publish_at' => $publishAt,
            'last_edited_by_admin_id' => $admin->id,
        ]);
    }

    public function unpublishPage(Page $page, Admin $admin): void
    {
        $page->update([
            'status' => 'draft',
            'last_edited_by_admin_id' => $admin->id,
        ]);
    }

    public function archivePage(Page $page, Admin $admin): void
    {
        $page->update([
            'status' => 'archived',
            'last_edited_by_admin_id' => $admin->id,
        ]);
    }

    public function clonePage(Page $page, Admin $admin): Page
    {
        return DB::transaction(function () use ($page, $admin) {
            $page->load(['sections', 'blocks.slides', 'blocks.adImageItems', 'blocks.blockProducts']);

            $clone = $page->replicate(['published_at', 'published_by_admin_id', 'version']);
            $clone->name = $page->name . ' (Copy)';
            $clone->slug = $page->slug . '-copy-' . now()->timestamp;
            $clone->status = 'draft';
            $clone->version = 1;
            $clone->last_edited_by_admin_id = $admin->id;
            $clone->save();

            // Clone sections
            $sectionIdMap = [];
            foreach ($page->sections as $section) {
                $newSection = $section->replicate();
                $newSection->page_id = $clone->id;
                $newSection->save();
                $sectionIdMap[$section->id] = $newSection->id;
            }

            // Clone blocks + sub-items
            foreach ($page->blocks as $block) {
                $newBlock = $block->replicate();
                $newBlock->page_id = $clone->id;
                $newBlock->section_id = isset($block->section_id) ? ($sectionIdMap[$block->section_id] ?? null) : null;
                $newBlock->created_by_admin_id = $admin->id;
                $newBlock->updated_by_admin_id = null;
                $newBlock->save();

                foreach ($block->slides as $slide) {
                    $s = $slide->replicate();
                    $s->page_block_id = $newBlock->id;
                    $s->save();
                }
                foreach ($block->adImageItems as $item) {
                    $a = $item->replicate();
                    $a->page_block_id = $newBlock->id;
                    $a->save();
                }
                foreach ($block->blockProducts as $bp) {
                    $p = $bp->replicate();
                    $p->page_block_id = $newBlock->id;
                    $p->save();
                }
            }

            return $clone;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Section management
    // ─────────────────────────────────────────────────────────────────────────

    public function addSection(Page $page, array $data): PageSection
    {
        $position = PageSection::where('page_id', $page->id)->max('position') + 1;

        return PageSection::create(array_merge($data, [
            'page_id' => $page->id,
            'position' => $position,
        ]));
    }

    public function updateSection(PageSection $section, array $data): void
    {
        $section->update($data);
    }

    public function deleteSection(PageSection $section): void
    {
        // Orphan blocks (set section_id null) rather than cascade delete
        PageBlock::where('section_id', $section->id)
            ->update(['section_id' => null]);

        $section->delete();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Block management
    // ─────────────────────────────────────────────────────────────────────────

    public function addBlock(Page $page, string $blockType, Admin $admin, ?string $sectionId = null): PageBlock
    {
        $position = PageBlock::where('page_id', $page->id)->max('position') + 1;

        return PageBlock::create([
            'page_id' => $page->id,
            'section_id' => $sectionId,
            'block_type' => $blockType,
            'position' => $position,
            'config' => [],
            'is_visible' => true,
            'created_by_admin_id' => $admin->id,
        ]);
    }

    public function updateBlock(PageBlock $block, array $config, array $meta, Admin $admin): void
    {
        $this->createBlockRevision($block, $admin, 'update');

        $update = ['updated_by_admin_id' => $admin->id];

        if (!empty($config)) {
            $update['config'] = array_merge($block->config ?? [], $config);
        }

        // Allowed meta fields that can be updated alongside config
        $allowedMeta = ['is_visible', 'visible_from', 'visible_until', 'device_target', 'audience', 'cache_ttl_seconds'];
        foreach ($allowedMeta as $field) {
            if (array_key_exists($field, $meta)) {
                $update[$field] = $meta[$field];
            }
        }

        $block->update($update);
    }

    public function deleteBlock(PageBlock $block): void
    {
        $block->delete();
    }

    public function reorderBlocks(Page $page, array $orderedIds): void
    {
        DB::transaction(function () use ($page, $orderedIds) {
            foreach ($orderedIds as $position => $id) {
                PageBlock::where('id', $id)
                    ->where('page_id', $page->id)
                    ->update(['position' => $position]);
            }
        });
    }

    public function toggleBlockVisibility(PageBlock $block, Admin $admin): void
    {
        $this->createBlockRevision($block, $admin, 'visibility_toggle');
        $block->update([
            'is_visible' => !$block->is_visible,
            'updated_by_admin_id' => $admin->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Slide management (hero_slider)
    // ─────────────────────────────────────────────────────────────────────────

    public function addSlide(PageBlock $block, array $data): SliderSlide
    {
        $position = SliderSlide::where('page_block_id', $block->id)->max('position') + 1;

        return SliderSlide::create(array_merge($data, [
            'page_block_id' => $block->id,
            'position' => $position,
        ]));
    }

    public function updateSlide(SliderSlide $slide, array $data): void
    {
        $slide->update($data);
    }

    public function deleteSlide(SliderSlide $slide): void
    {
        $slide->delete();
    }

    public function reorderSlides(PageBlock $block, array $orderedIds): void
    {
        DB::transaction(function () use ($block, $orderedIds) {
            foreach ($orderedIds as $position => $id) {
                SliderSlide::where('id', $id)
                    ->where('page_block_id', $block->id)
                    ->update(['position' => $position]);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ad image item management
    // ─────────────────────────────────────────────────────────────────────────

    public function addAdImage(PageBlock $block, array $data): AdImageItem
    {
        $position = AdImageItem::where('page_block_id', $block->id)->max('position') + 1;

        return AdImageItem::create(array_merge($data, [
            'page_block_id' => $block->id,
            'position' => $position,
        ]));
    }

    public function updateAdImage(AdImageItem $item, array $data): void
    {
        $item->update($data);
    }

    public function deleteAdImage(AdImageItem $item): void
    {
        $item->delete();
    }

    public function reorderAdImages(PageBlock $block, array $orderedIds): void
    {
        DB::transaction(function () use ($block, $orderedIds) {
            foreach ($orderedIds as $position => $id) {
                AdImageItem::where('id', $id)
                    ->where('page_block_id', $block->id)
                    ->update(['position' => $position]);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Block product management
    // ─────────────────────────────────────────────────────────────────────────

    public function addBlockProduct(PageBlock $block, string $variantId, Admin $admin): PageBlockProduct
    {
        $position = PageBlockProduct::where('page_block_id', $block->id)->max('position') + 1;

        return PageBlockProduct::create([
            'page_block_id' => $block->id,
            'product_variant_id' => $variantId,
            'position' => $position,
            'added_by_admin_id' => $admin->id,
        ]);
    }

    public function removeBlockProduct(PageBlockProduct $blockProduct): void
    {
        $blockProduct->delete();
    }

    public function reorderBlockProducts(PageBlock $block, array $orderedIds): void
    {
        DB::transaction(function () use ($block, $orderedIds) {
            foreach ($orderedIds as $position => $id) {
                PageBlockProduct::where('id', $id)
                    ->where('page_block_id', $block->id)
                    ->update(['position' => $position]);
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function createBlockRevision(PageBlock $block, Admin $admin, string $changeType, ?string $reason = null): void
    {
        $lastRevision = PageBlockRevision::where('page_block_id', $block->id)->max('revision_number');

        PageBlockRevision::create([
            'page_block_id' => $block->id,
            'page_id' => $block->page_id,
            'revision_number' => ($lastRevision ?? 0) + 1,
            'config_snapshot' => $block->config,
            'is_visible_snapshot' => $block->is_visible,
            'position_snapshot' => $block->position,
            'changed_by_admin_id' => $admin->id,
            'change_reason' => $reason,
            'change_type' => $changeType,
        ]);
    }

    private function createPageRevision(Page $page, Admin $admin, ?string $reason = null): void
    {
        // Capture a full snapshot of the live blocks at publish time
        $blocksSnapshot = PageBlock::with(['slides', 'adImageItems', 'blockProducts'])
            ->where('page_id', $page->id)
            ->orderBy('position')
            ->get()
            ->toArray();

        PageRevision::create([
            'page_id' => $page->id,
            'version' => $page->version,
            'blocks_snapshot' => $blocksSnapshot,
            'published_by_admin_id' => $admin->id,
            'publish_reason' => $reason,
        ]);
    }
}
