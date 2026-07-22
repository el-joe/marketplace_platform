@component('admin.docs._layout', ['title' => __('docs/features/influencer-deals.title'), 'icon' => '🌟', 'breadcrumb' => __('docs/features/influencer-deals.breadcrumb')])

    <div class="prose prose-sm max-w-none space-y-10">

        {{-- 1. What It Is --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/influencer-deals.what_it_is.heading') }}</h2>
            <p class="text-gray-600">{{ __('docs/features/influencer-deals.what_it_is.p1') }}</p>
            <p class="text-gray-600">{{ __('docs/features/influencer-deals.what_it_is.p2') }}</p>
        </section>

        {{-- 2. How It Works --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/influencer-deals.how_it_works.heading') }}</h2>
            <ol class="list-decimal list-inside text-gray-600 space-y-1">
                <li>{{ __('docs/features/influencer-deals.how_it_works.step1') }} <code>/admin/influencer-deals/propose</code>: {{ __('docs/features/influencer-deals.how_it_works.step1_fields') }}</li>
                <li>{{ __('docs/features/influencer-deals.how_it_works.step2') }}</li>
                <li>{{ __('docs/features/influencer-deals.how_it_works.step3') }}</li>
                <li>{{ __('docs/features/influencer-deals.how_it_works.step4') }}</li>
                <li>{{ __('docs/features/influencer-deals.how_it_works.step5') }}</li>
                <li>{{ __('docs/features/influencer-deals.how_it_works.step6') }}</li>
            </ol>
        </section>

        {{-- 3. Deliverables --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/influencer-deals.deliverables.heading') }}</h2>
            <p class="text-gray-600">{{ __('docs/features/influencer-deals.deliverables.p1') }}</p>
            <p class="text-gray-600">{{ __('docs/features/influencer-deals.deliverables.p2') }}</p>
        </section>

        {{-- 4. Payment --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900">{{ __('docs/features/influencer-deals.payment.heading') }}</h2>
            <p class="text-gray-600">{{ __('docs/features/influencer-deals.payment.p1') }}</p>
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mt-2 text-amber-800 text-sm">
                {{ __('docs/features/influencer-deals.payment.notice') }}
            </div>
        </section>

    </div>

@endcomponent
