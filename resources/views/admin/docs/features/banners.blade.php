@component('admin.docs._layout', ['title' => 'Banners', 'icon' => '🖼️', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What Banners Are --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">1. What Banners Are</h2>
            <p class="text-gray-600">Promotional image banners that appear in fixed placement slots across the storefront and all panel homepages.</p>
            <p class="text-gray-600">Different from Page Builder <code>ad_images</code> blocks &mdash; banners are managed centrally with named placements.</p>
        </section>

        {{-- 2. Placements --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">2. Placements</h2>
            <p class="text-gray-600"><code>/admin/banners/placements</code> &mdash; Lists all available placement slots. Each banner is assigned to one placement.</p>
            <p class="text-gray-700 font-medium mb-2 mt-3">Placement examples</p>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['home_top', 'category_sidebar', 'checkout_top', 'partner_login_hero'] as $placement)
                    <span class="px-3 py-1.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium border border-primary-200">{{ $placement }}</span>
                @endforeach
            </div>
        </section>

        {{-- 3. Banner Lifecycle --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">3. Banner Lifecycle</h2>
            <p class="text-gray-600">create &rarr; upload image &rarr; set dates (start/end) &rarr; set target URL &rarr; assign placement</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Active:</strong> <code>now()</code> between <code>start_date</code> and <code>end_date</code></li>
                <li><strong>Expired:</strong> <code>end_date</code> passed (kept for history)</li>
                <li>Multiple banners per placement = rotation (sorted by <code>sort_order</code> or random)</li>
            </ul>
        </section>

        {{-- 4. Admin Actions --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">4. Admin Actions</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Duplicate:</strong> clone a banner for rapid A/B testing</li>
                <li><strong>Bulk:</strong> activate/deactivate/delete multiple at once</li>
                <li><strong>Upload image:</strong> separate FilePond endpoint per banner</li>
            </ul>
        </section>

    </div>

@endcomponent
