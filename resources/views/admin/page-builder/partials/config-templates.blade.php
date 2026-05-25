{{--
Block config modal body templates.
Rendered client-side by page-builder.js based on block_type.
Each <template data-type="..."> tag holds the HTML for that block type's config form.
    JS clones the appropriate template into #block-config-body.
    --}}
    <div id="block-config-templates" style="display:none">

        {{-- ── hero_slider ──────────────────────────────────────────────────── --}}
        <template data-type="hero_slider">
            <div class="space-y-5">
                {{-- Slider settings --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Height (Desktop)</label>
                        <input type="text" name="config[height_desktop]" class="form-input w-full" placeholder="480px">
                    </div>
                    <div>
                        <label class="form-label">Height (Mobile)</label>
                        <input type="text" name="config[height_mobile]" class="form-input w-full" placeholder="260px">
                    </div>
                    <div>
                        <label class="form-label">Autoplay Interval (ms)</label>
                        <input type="number" name="config[autoplay_interval]" class="form-input w-full" value="5000"
                            min="1000" step="500">
                    </div>
                </div>
                <div class="flex gap-4">
                    <x-form-toggle name="config[show_dots]" label="Show Dots" :value="true" class="flex-1" />
                    <x-form-toggle name="config[show_arrows]" label="Show Arrows" :value="true" class="flex-1" />
                </div>

                {{-- Slides manager --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="form-label mb-0">Slides</label>
                        <button type="button" class="btn-add-slide btn btn-secondary btn-xs">+ Add Slide</button>
                    </div>
                    <div id="slides-list" class="space-y-2 min-h-[40px]"></div>
                </div>
            </div>
        </template>

        {{-- ── ad_images (2col / 3col / 4col / split_banner) ───────────────── --}}
        <template data-type="ad_images">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Gap Between Images</label>
                        <input type="text" name="config[gap]" class="form-input w-full" placeholder="4px">
                    </div>
                    <div>
                        <label class="form-label">Border Radius</label>
                        <input type="text" name="config[border_radius]" class="form-input w-full" placeholder="8px">
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="form-label mb-0">Ad Images</label>
                        <button type="button" class="btn-add-ad-image btn btn-secondary btn-xs">+ Add Image</button>
                    </div>
                    <div id="ad-images-list" class="space-y-2 min-h-[40px]"></div>
                </div>
            </div>
        </template>

        {{-- ── product_grid / product_row ───────────────────────────────────── --}}
        <template data-type="product_list">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Title (EN)</label>
                        <input type="text" name="config[title_en]" class="form-input w-full" maxlength="150">
                    </div>
                    <div>
                        <label class="form-label">Title (AR)</label>
                        <input type="text" name="config[title_ar]" class="form-input w-full text-right" dir="rtl"
                            maxlength="150">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Data Source</label>
                        <select name="config[data_source]" class="form-select w-full">
                            <option value="curated">Curated (manual)</option>
                            <option value="category">By Category</option>
                            <option value="top_selling">Top Selling</option>
                            <option value="newest">Newest</option>
                            <option value="flash_sale">Flash Sale</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Max Products</label>
                        <input type="number" name="config[limit]" class="form-input w-full" value="10" min="1" max="50">
                    </div>
                    <div>
                        <label class="form-label">Cols (Desktop)</label>
                        <input type="number" name="config[cols_desktop]" class="form-input w-full" value="4" min="1"
                            max="6">
                    </div>
                    <div>
                        <label class="form-label">Cols (Mobile)</label>
                        <input type="number" name="config[cols_mobile]" class="form-input w-full" value="2" min="1"
                            max="4">
                    </div>
                </div>

                <div class="flex gap-4">
                    <x-form-toggle name="config[show_price]" label="Show Price" :value="true" class="flex-1" />
                    <x-form-toggle name="config[show_add_to_cart]" label="Add-to-Cart" :value="true" class="flex-1" />
                </div>

                {{-- Curated products list (shown when data_source = curated) --}}
                <div id="curated-products-wrap">
                    <div class="flex items-center justify-between mb-2">
                        <label class="form-label mb-0">Curated Products</label>
                    </div>
                    <div class="mb-2 flex gap-2">
                        <input type="text" id="product-search-input" class="form-input flex-1 text-sm"
                            placeholder="Search product variants…">
                        <button type="button" id="btn-product-search" class="btn btn-secondary btn-sm">Search</button>
                    </div>
                    <div id="product-search-results" class="space-y-1 mb-3 hidden"></div>
                    <div id="block-products-list" class="space-y-2 min-h-[40px]"></div>
                </div>
            </div>
        </template>

        {{-- ── html_block ───────────────────────────────────────────────────── --}}
        <template data-type="html_block">
            <div class="space-y-4">
                <div>
                    <label class="form-label">HTML Content (EN)</label>
                    <textarea name="config[html_en]" rows="8" class="form-textarea w-full font-mono text-sm"></textarea>
                </div>
                <div>
                    <label class="form-label">HTML Content (AR)</label>
                    <textarea name="config[html_ar]" rows="8" class="form-textarea w-full font-mono text-sm text-right"
                        dir="rtl"></textarea>
                </div>
            </div>
        </template>

        {{-- ── category_grid ─────────────────────────────────────────────────── --}}
        <template data-type="category_grid">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Title (EN)</label>
                        <input type="text" name="config[title_en]" class="form-input w-full" maxlength="150">
                    </div>
                    <div>
                        <label class="form-label">Title (AR)</label>
                        <input type="text" name="config[title_ar]" class="form-input w-full text-right" dir="rtl"
                            maxlength="150">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Columns</label>
                        <input type="number" name="config[cols]" class="form-input w-full" value="5" min="2" max="8">
                    </div>
                    <div>
                        <label class="form-label">Layout</label>
                        <select name="config[layout]" class="form-select w-full">
                            <option value="grid">Grid</option>
                            <option value="horizontal_scroll">Horizontal Scroll</option>
                        </select>
                    </div>
                </div>
            </div>
        </template>

        {{-- ── countdown_timer ──────────────────────────────────────────────── --}}
        <template data-type="countdown_timer">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Title (EN)</label>
                        <input type="text" name="config[title_en]" class="form-input w-full" maxlength="100">
                    </div>
                    <div>
                        <label class="form-label">Title (AR)</label>
                        <input type="text" name="config[title_ar]" class="form-input w-full text-right" dir="rtl"
                            maxlength="100">
                    </div>
                </div>
                <div>
                    <label class="form-label">Ends At <span class="text-danger-500">*</span></label>
                    <input type="text" name="config[ends_at]" class="form-input w-full flatpickr-datetime">
                </div>
            </div>
        </template>

        {{-- ── Shared visibility / scheduling tab (appended to all configs) ── --}}
        <div id="block-meta-fields" class="border-t border-gray-100 pt-4 mt-4 space-y-4">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Display Settings</h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Visible From</label>
                    <input type="text" name="meta[visible_from]" class="form-input w-full flatpickr-datetime"
                        placeholder="Always">
                </div>
                <div>
                    <label class="form-label">Visible Until</label>
                    <input type="text" name="meta[visible_until]" class="form-input w-full flatpickr-datetime"
                        placeholder="Always">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Device Target</label>
                    <select name="meta[device_target]" class="form-select w-full">
                        <option value="all">All Devices</option>
                        <option value="desktop">Desktop Only</option>
                        <option value="mobile">Mobile Only</option>
                        <option value="app">App Only</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Cache TTL (seconds)</label>
                    <input type="number" name="meta[cache_ttl_seconds]" class="form-input w-full" value="60" min="0">
                </div>
            </div>
        </div>

    </div>