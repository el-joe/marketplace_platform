@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="{{ __('admin.page_builder.config_forms.title_en') }}" :value="$config['title_en'] ?? 'Deal of the Day'" dir="ltr" />
        <x-form.input name="title_ar" label="{{ __('admin.page_builder.config_forms.title_ar') }}" :value="$config['title_ar'] ?? ''" dir="rtl" />
    </div>

    <x-form.date-picker name="ends_at" label="{{ __('admin.page_builder.config_forms.deal_of_day.deal_ends_at') }}" enableTime :value="$config['ends_at'] ?? ''" class="mt-3" />

    <x-form.select name="source" label="{{ __('admin.page_builder.config_forms.deal_of_day.product_source') }}" :value="$config['source'] ?? 'manual'" class="mt-3">
        <option value="manual">{{ __('admin.page_builder.config_forms.deal_of_day.source_manual') }}</option>
        <option value="flash_sale">{{ __('admin.page_builder.config_forms.deal_of_day.source_flash_sale') }}</option>
    </x-form.select>

    <div class="space-y-1 mt-3">
        <label class="block text-sm font-medium text-gray-700">{{ __('admin.page_builder.config_forms.deal_of_day.flash_sale') }}</label>
        <select name="flash_sale_id" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
            <option value="">{{ __('admin.page_builder.config_forms.deal_of_day.select_placeholder') }}</option>
            @foreach ($flashSales as $fs)
                <option value="{{ $fs->id }}" {{ ($config['flash_sale_id'] ?? '') == $fs->id ? 'selected' : '' }}>
                    {{ $fs->name_en }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500">{{ __('admin.page_builder.config_forms.deal_of_day.flash_sale_help') }}</p>
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="background_color" type="color" label="{{ __('admin.page_builder.config_forms.deal_of_day.background_color') }}" :value="$config['background_color'] ?? '#ffffff'" />
        <x-form.input name="text_color" type="color" label="{{ __('admin.page_builder.config_forms.deal_of_day.text_color') }}" :value="$config['text_color'] ?? '#111827'" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.toggle name="show_countdown_timer" label="{{ __('admin.page_builder.config_forms.deal_of_day.show_countdown') }}" :value="$config['show_countdown_timer'] ?? true" />
        <x-form.toggle name="show_discount_badge" label="{{ __('admin.page_builder.config_forms.deal_of_day.discount_badge') }}" :value="$config['show_discount_badge'] ?? true" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>