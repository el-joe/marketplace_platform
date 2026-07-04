@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="{{ __('admin.page_builder.config_forms.title_en') }}" :value="$config['title_en'] ?? ''" dir="ltr" />
        <x-form.input name="title_ar" label="{{ __('admin.page_builder.config_forms.title_ar') }}" :value="$config['title_ar'] ?? ''" dir="rtl" />
    </div>

    <x-form.select name="source" label="{{ __('admin.page_builder.config_forms.product_row.source') }}" :value="$config['source'] ?? 'best_sellers'" class="mt-3">
        <option value="best_sellers">{{ __('admin.page_builder.config_forms.product_row.source_best_sellers') }}</option>
        <option value="new_arrivals">{{ __('admin.page_builder.config_forms.product_row.source_new_arrivals') }}</option>
        <option value="top_rated">{{ __('admin.page_builder.config_forms.product_row.source_top_rated') }}</option>
        <option value="trending">{{ __('admin.page_builder.config_forms.product_row.source_trending') }}</option>
        <option value="category">{{ __('admin.page_builder.config_forms.product_row.source_category') }}</option>
        <option value="flash_sale">{{ __('admin.page_builder.config_forms.product_row.source_flash_sale') }}</option>
        <option value="manual">{{ __('admin.page_builder.config_forms.product_row.source_manual') }}</option>
        <option value="personalized">{{ __('admin.page_builder.config_forms.product_row.source_personalized') }}</option>
    </x-form.select>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">{{ __('admin.page_builder.config_forms.product_row.category') }}</label>
            <select name="category_id" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                <option value="">{{ __('admin.page_builder.config_forms.product_row.none_option') }}</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ ($config['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name_en }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">{{ __('admin.page_builder.config_forms.product_row.flash_sale') }}</label>
            <select name="flash_sale_id" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                <option value="">{{ __('admin.page_builder.config_forms.product_row.none_option') }}</option>
                @foreach ($flashSales as $fs)
                    <option value="{{ $fs->id }}" {{ ($config['flash_sale_id'] ?? '') == $fs->id ? 'selected' : '' }}>
                        {{ $fs->name_en }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="items_per_row" type="number" label="{{ __('admin.page_builder.config_forms.product_row.items_per_row') }}" :value="$config['items_per_row'] ?? 4" min="1" max="8" />
        <x-form.input name="max_products"  type="number" label="{{ __('admin.page_builder.config_forms.product_row.max_products') }}"  :value="$config['max_products']  ?? 12" min="1" max="50" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.toggle name="show_view_all"  label="{{ __('admin.page_builder.config_forms.product_row.view_all_link') }}" :value="$config['show_view_all'] ?? true" />
        <x-form.toggle name="scrollable_row" label="{{ __('admin.page_builder.config_forms.product_row.scrollable') }}"    :value="$config['scrollable_row'] ?? false" />
    </div>
    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.toggle name="show_ratings" label="{{ __('admin.page_builder.config_forms.product_row.ratings') }}" :value="$config['show_ratings'] ?? true" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
