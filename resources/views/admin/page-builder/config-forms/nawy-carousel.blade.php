@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="{{ __('admin.page_builder.config_forms.title_en') }}" :value="$config['title_en'] ?? ''" placeholder="Now Nawy" dir="ltr" />
        <x-form.input name="title_ar" label="{{ __('admin.page_builder.config_forms.title_ar') }}" :value="$config['title_ar'] ?? ''" placeholder="كاروسيل Now Nawy" dir="rtl" />
    </div>

    <x-form.select name="fulfillment_filter" label="{{ __('admin.page_builder.config_forms.nawy_carousel.fulfillment_filter') }}" :value="$config['fulfillment_filter'] ?? 'all'" class="mt-3">
        <option value="all">{{ __('admin.page_builder.config_forms.nawy_carousel.all_types') }}</option>
        <option value="express">{{ __('admin.page_builder.config_forms.nawy_carousel.express_only') }}</option>
        <option value="global">{{ __('admin.page_builder.config_forms.nawy_carousel.global_only') }}</option>
    </x-form.select>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="category_id" label="{{ __('admin.page_builder.config_forms.nawy_carousel.category_id_optional') }}" :value="$config['category_id'] ?? ''" placeholder="{{ __('admin.page_builder.config_forms.nawy_carousel.leave_blank_all') }}" />
        <x-form.input name="limit" type="number" label="{{ __('admin.page_builder.config_forms.nawy_carousel.max_items') }}" :value="$config['limit'] ?? 10" min="1" max="50" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
