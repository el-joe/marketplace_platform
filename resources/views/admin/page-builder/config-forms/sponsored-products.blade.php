@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="{{ __('admin.page_builder.config_forms.title_en') }}" :value="$config['title_en'] ?? ''" dir="ltr" />
        <x-form.input name="title_ar" label="{{ __('admin.page_builder.config_forms.title_ar') }}" :value="$config['title_ar'] ?? ''" dir="rtl" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="max_items" type="number" label="{{ __('admin.page_builder.config_forms.sponsored_products.max_products') }}" :value="$config['max_items'] ?? 8" min="1"
            max="30" />
        <x-form.input name="items_per_row" type="number" label="{{ __('admin.page_builder.config_forms.sponsored_products.items_per_row') }}" :value="$config['items_per_row'] ?? 4"
            min="1" max="8" />
    </div>

    <x-form.select name="source" label="{{ __('admin.page_builder.config_forms.sponsored_products.ad_source') }}" :value="$config['source'] ?? 'active_campaigns'" class="mt-3">
        <option value="active_campaigns">{{ __('admin.page_builder.config_forms.sponsored_products.active_campaigns') }}</option>
        <option value="manual">{{ __('admin.page_builder.config_forms.sponsored_products.manual_selection') }}</option>
    </x-form.select>

    <div class="grid grid-cols-3 gap-3 mt-3">
        <x-form.toggle name="show_sponsored_badge" label="{{ __('admin.page_builder.config_forms.sponsored_products.sponsored_badge') }}" :value="$config['show_sponsored_badge'] ?? true" />
        <x-form.toggle name="scrollable_row" label="{{ __('admin.page_builder.config_forms.sponsored_products.scrollable') }}" :value="$config['scrollable_row'] ?? false" />
        <x-form.toggle name="show_ratings" label="{{ __('admin.page_builder.config_forms.sponsored_products.ratings') }}" :value="$config['show_ratings'] ?? true" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>