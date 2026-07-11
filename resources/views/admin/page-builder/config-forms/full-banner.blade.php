@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <div class="space-y-1">
        <label class="block text-sm font-medium text-gray-700">{{ __('admin.page_builder.config_forms.full_banner.banner') }}</label>
        <select name="banner_id" required class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
            <option value="">{{ __('admin.page_builder.config_forms.full_banner.select_banner_placeholder') }}</option>
            @foreach ($banners as $banner)
                <option value="{{ $banner->id }}" {{ ($config['banner_id'] ?? '') == $banner->id ? 'selected' : '' }}>
                    {{ $banner->name }}
                </option>
            @endforeach
        </select>
    </div>

    <x-form.input name="link_url" label="{{ __('admin.page_builder.config_forms.full_banner.link_url') }}" :value="$config['link_url'] ?? ''" class="mt-3" />

    <x-form.select name="link_type" label="{{ __('admin.page_builder.config_forms.full_banner.link_type') }}" :value="$config['link_type'] ?? 'url'" class="mt-3">
        <option value="url">{{ __('admin.page_builder.config_forms.full_banner.link_type_url') }}</option>
        <option value="product">{{ __('admin.page_builder.config_forms.full_banner.link_type_product') }}</option>
        <option value="category">{{ __('admin.page_builder.config_forms.full_banner.link_type_category') }}</option>
        <option value="brand">{{ __('admin.page_builder.config_forms.full_banner.link_type_brand') }}</option>
        <option value="flash_sale">{{ __('admin.page_builder.config_forms.full_banner.link_type_flash_sale') }}</option>
    </x-form.select>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="aspect_ratio"         label="{{ __('admin.page_builder.config_forms.full_banner.desktop_ratio') }}" :value="$config['aspect_ratio']        ?? '4:1'" />
        <x-form.input name="mobile_aspect_ratio"  label="{{ __('admin.page_builder.config_forms.full_banner.mobile_ratio') }}"  :value="$config['mobile_aspect_ratio'] ?? '2:1'" />
    </div>

    <div class="mt-4 text-xs text-gray-500">
        {{ __('admin.page_builder.config_forms.full_banner.select_banner_help') }}
        <a href="{{ route('admin.banners.index') }}" target="_blank" class="text-primary-600 hover:underline">{{ __('admin.page_builder.config_forms.full_banner.banners_page_link') }}</a>.
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
