@component('admin.docs._layout', ['title' => 'Page Builder', 'icon' => '🏗️', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What It Is</h2>
            <p class="text-gray-600">A drag-and-drop visual builder for storefront pages. Admin creates pages, adds blocks, configures each block's content, and publishes.</p>
            <p class="text-gray-600">Revisions are tracked &mdash; any block or page version can be restored. Blocks are append-only models (<code>PageBlockRevision</code>, <code>PageRevision</code>) &mdash; updates create new revisions.</p>
        </section>

        {{-- 2. How It Works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. How It Works</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>Admin opens <code>/admin/page-builder</code></li>
                <li>Selects or creates a page (home, category landing, promo page, etc.)</li>
                <li>Adds blocks to the page (each block has a type)</li>
                <li>Configures each block via the config panel (slides, products, categories, etc.)</li>
                <li>Publishes the page &rarr; storefront immediately reflects changes</li>
            </ol>
        </section>

        {{-- 3. Block Types --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Block Types and What They Do</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left text-gray-600 border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="px-4 py-2 font-medium">Block Type</th>
                            <th class="px-4 py-2 font-medium">Purpose</th>
                            <th class="px-4 py-2 font-medium">Content Sources</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr><td class="px-4 py-2"><code>hero_slider</code></td><td class="px-4 py-2">Full-width image carousel at top of page</td><td class="px-4 py-2">Slides with image, title, CTA URL</td></tr>
                        <tr><td class="px-4 py-2"><code>ad_images</code></td><td class="px-4 py-2">Grid of promotional banner images</td><td class="px-4 py-2">Ad images with links</td></tr>
                        <tr><td class="px-4 py-2"><code>category_pills</code></td><td class="px-4 py-2">Horizontal scrollable category chips</td><td class="px-4 py-2">Categories from catalog</td></tr>
                        <tr><td class="px-4 py-2"><code>brand_strip</code></td><td class="px-4 py-2">Horizontal brand logo row</td><td class="px-4 py-2">Brands/Sellers</td></tr>
                        <tr><td class="px-4 py-2"><code>product_grid</code></td><td class="px-4 py-2">Manual product selection grid</td><td class="px-4 py-2">Products (manually picked)</td></tr>
                        <tr><td class="px-4 py-2"><code>product_carousel</code></td><td class="px-4 py-2">Auto or manual product carousel</td><td class="px-4 py-2">Products or dynamic source</td></tr>
                        <tr><td class="px-4 py-2"><code>flash_sale_timer</code></td><td class="px-4 py-2">Countdown clock tied to a flash sale event</td><td class="px-4 py-2">Links to flash_sale</td></tr>
                        <tr><td class="px-4 py-2"><code>featured_vendor</code></td><td class="px-4 py-2">Vendor spotlight block</td><td class="px-4 py-2">Single vendor</td></tr>
                        <tr><td class="px-4 py-2"><code>custom_html</code></td><td class="px-4 py-2">Raw HTML block for one-offs</td><td class="px-4 py-2">Inline HTML</td></tr>
                        <tr><td class="px-4 py-2"><code>banner_single</code></td><td class="px-4 py-2">Single full-width promo banner</td><td class="px-4 py-2">Banner image + URL</td></tr>
                        <tr><td class="px-4 py-2"><code>now_nawy_feed</code></td><td class="px-4 py-2">Admin product listings (Nawy Now)</td><td class="px-4 py-2">admin_product_listings</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- 4. Slides Management --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Slides Management</h2>
            <p class="text-gray-600">Each <code>hero_slider</code> block has slides. Admin can add, reorder (drag), and delete slides.</p>
            <p class="text-gray-600">Each slide: image (FilePond upload), <code>title_en</code>, <code>title_ar</code>, subtitle, CTA URL, CTA text.</p>
        </section>

        {{-- 5. Ad Images --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">5. Ad Images</h2>
            <p class="text-gray-600">Each <code>ad_images</code> block has ad images. Used for promotional grid (like Noon's category banners). Each: image, click URL, alt text, sort order.</p>
        </section>

        {{-- 6. Product Pickers --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">6. Product Pickers</h2>
            <p class="text-gray-600"><code>product_grid</code> and <code>product_carousel</code> blocks let admin search and pin specific products. Search by name/SKU &rarr; add to block &rarr; reorder via drag.</p>
        </section>

        {{-- 7. Revisions & Restore --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">7. Revisions & Restore</h2>
            <p class="text-gray-600">Every block config save creates a <code>PageBlockRevision</code> row. Every page publish creates a <code>PageRevision</code> row.</p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2 text-amber-800 text-sm">
                Both are <strong>APPEND-ONLY</strong> &mdash; no updates or deletes on revision models. Admin can view revision history and restore any prior version.
            </div>
        </section>

        {{-- 8. Block Visibility --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">8. Block Visibility</h2>
            <p class="text-gray-600">Each block can be toggled visible/hidden without deleting it. Useful for seasonal content (e.g. hide Ramadan block after Eid).</p>
        </section>

        {{-- 9. Block Analytics --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">9. Block Analytics</h2>
            <p class="text-gray-600"><code>/admin/page-builder/blocks/{block}/analytics</code> &mdash; Impressions, clicks, and CTR per block (if tracking is wired in storefront JS).</p>
        </section>

    </div>

@endcomponent
