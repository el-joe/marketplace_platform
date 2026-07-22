@component('admin.docs._layout', ['title' => 'Content Pages', 'icon' => '📝', 'breadcrumb' => 'Features'])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- What it is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">What It Is</h2>
            <p class="text-gray-600">Everything in the admin sidebar's <strong>Content</strong> group: page builder, storefront/app configuration, reviews moderation, the blog, the vendor knowledge hub, FAQs, static portal pages, dynamic content settings, and radio channels.</p>
        </section>

        {{-- Pages --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Pages (Page Builder)</h2>
            <p class="text-gray-600">See the <a href="{{ route('admin.docs.features.page-builder') }}" class="text-primary-600 hover:underline">Page Builder</a> documentation.</p>
        </section>

        {{-- App Contexts --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">App Contexts</h2>
            <p class="text-gray-600"><a href="{{ route('admin.app-contexts.index') }}" class="text-primary-600 hover:underline">admin/app-contexts</a>: configures the top strip on the mobile app.</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Contexts: Main Marketplace, Super Mall, Food, 15-Minute Delivery, etc.</li>
                <li>Each context: assign countries, configure bottom navigation items</li>
            </ul>
        </section>

        {{-- Reviews --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Reviews</h2>
            <p class="text-gray-600"><a href="{{ route('admin.reviews.index') }}" class="text-primary-600 hover:underline">admin/reviews</a>: all product reviews submitted by customers.</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Moderation: approve, reject, delete</li>
                <li>Vendor reply management: show/hide vendor responses</li>
                <li>Bulk actions: approve or reject multiple at once</li>
            </ul>
        </section>

        {{-- Blog --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Blog Categories + Blog Posts</h2>
            <p class="text-gray-600">Vendor- and customer-facing blog.</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><a href="{{ route('admin.blog.categories.index') }}" class="text-primary-600 hover:underline">admin/blog/categories</a>: nested, drag-to-reorder</li>
                <li><a href="{{ route('admin.blog.posts.index') }}" class="text-primary-600 hover:underline">admin/blog/posts</a>: bilingual (EN/AR), Summernote editor, tags, featured flag, archive</li>
                <li>Attachments: PDFs and files per post (FilePond uploads)</li>
                <li>View counter: public endpoint increments views, throttled 60/min</li>
            </ul>
        </section>

        {{-- Knowledge Hub --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Knowledge Hub (Collections + Articles)</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><a href="{{ route('admin.adsupport.collections.index') }}" class="text-primary-600 hover:underline">admin/adsupport/collections</a>: topic collections for the vendor knowledge base</li>
                <li><a href="{{ route('admin.adsupport.articles.index') }}" class="text-primary-600 hover:underline">admin/adsupport/articles</a>: articles within each collection</li>
                <li>Feature: pin important articles to the top</li>
            </ul>
        </section>

        {{-- FAQs --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">FAQs</h2>
            <p class="text-gray-600"><a href="{{ route('admin.faqs.index') }}" class="text-primary-600 hover:underline">admin/faqs</a>: public-facing FAQ entries. Reorder via drag, toggle active, bilingual.</p>
        </section>

        {{-- Portal Content --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Portal Content</h2>
            <p class="text-gray-600"><a href="{{ route('admin.portal-content.index') }}" class="text-primary-600 hover:underline">admin/portal-content</a>: static page content editor.</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Pages: About, Terms of Service, Privacy Policy, Return Policy, Vendor Agreement, etc.</li>
                <li>Summernote editor for each language</li>
            </ul>
        </section>

        {{-- Content Settings --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Content Settings</h2>
            <p class="text-gray-600"><a href="{{ route('admin.content-settings.index') }}" class="text-primary-600 hover:underline">admin/content-settings</a>: all dynamic media, text, and URLs across the platform.</p>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>12 setting types: text, textarea, editor, file, url, email, phone, number, boolean, color, select, json</li>
                <li>Groups: general, appearance, homepage, footer, auth_pages, vendor_portal, emails, seo, policies, notifications</li>
                <li>Changes apply instantly via View Composer cache (5-minute TTL, cleared on save)</li>
            </ul>
        </section>

        {{-- Radio --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Radio Channels</h2>
            <p class="text-gray-600">Online radio stations embedded in the storefront &mdash; see the <a href="{{ route('admin.docs.features.radio') }}" class="text-primary-600 hover:underline">Radio Channels</a> doc for details. Customer route: <code>{country}.domain/radio/{channel}</code>.</p>
        </section>

        {{-- Who uses it / rules --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">Who Uses It & Key Rules</h2>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li><strong>Admin</strong> owns all content entries; nothing here is editable by vendors or customers</li>
                <li>All bilingual content requires both EN and AR before it can be marked active in most sections</li>
                <li>Content settings changes are cached (5 min) and cleared automatically on save &mdash; no manual cache-busting needed</li>
            </ul>
        </section>

    </div>

@endcomponent
