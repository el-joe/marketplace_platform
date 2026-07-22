@component('admin.docs._layout', ['title' => __('docs/features/secret-promotions.title'), 'icon' => '🔒', 'breadcrumb' => __('docs/features/secret-promotions.breadcrumb')])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/secret-promotions.what_it_is.heading') }}</h2>
            <p class="text-gray-600">{{ __('docs/features/secret-promotions.what_it_is.p1') }}</p>
            <p class="text-gray-600">{{ __('docs/features/secret-promotions.what_it_is.p2') }}</p>
        </section>

        {{-- 2. Key Security Rule --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/secret-promotions.security_rule.heading') }}</h2>
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-red-800 text-sm">
                <code>admin_share_pct</code> {{ __('docs/features/secret-promotions.security_rule.and') }} <code>product_value</code> {{ __('docs/features/secret-promotions.security_rule.p1') }}
            </div>
        </section>

        {{-- 3. How It Works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/secret-promotions.how_it_works.heading') }}</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>{{ __('docs/features/secret-promotions.how_it_works.step1') }} <code>/admin/marketers-secret-promotions</code>: {{ __('docs/features/secret-promotions.how_it_works.step1_fields') }}</li>
                <li>{{ __('docs/features/secret-promotions.how_it_works.step2') }}</li>
                <li>{{ __('docs/features/secret-promotions.how_it_works.step3') }}</li>
                <li>{{ __('docs/features/secret-promotions.how_it_works.step4') }}</li>
            </ol>
        </section>

        {{-- 4. Statuses --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/secret-promotions.statuses.heading') }}</h2>
            <div class="flex flex-wrap items-center gap-2 mb-4">
                @foreach (['draft', 'pending', 'approved', 'active', 'expired'] as $status)
                    <span class="px-3 py-1.5 rounded-full bg-primary-50 text-primary-700 text-xs font-medium border border-primary-200">{{ $status }}</span>
                    @if (!$loop->last)
                        <span class="text-gray-400">&rarr;</span>
                    @endif
                @endforeach
                <span class="text-gray-300">|</span>
                <span class="px-3 py-1.5 rounded-full bg-red-50 text-red-700 text-xs font-medium border border-red-200">rejected</span>
            </div>
            <p class="text-gray-600">{{ __('docs/features/secret-promotions.statuses.p1') }}</p>
        </section>

        {{-- 5. Marketer View --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/secret-promotions.marketer_view.heading') }}</h2>
            <p class="text-gray-600">{{ __('docs/features/secret-promotions.marketer_view.p1') }} <code>/secret-promotions</code>. {{ __('docs/features/secret-promotions.marketer_view.p2') }}</p>
            <p class="text-gray-600">{{ __('docs/features/secret-promotions.marketer_view.p3') }}</p>
        </section>

    </div>

@endcomponent
