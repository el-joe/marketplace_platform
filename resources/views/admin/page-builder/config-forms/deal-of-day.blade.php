@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="{{ __('admin.page_builder.config_forms.title_en') }}" :value="$config['title_en'] ?? 'Deal of the Day'" dir="ltr" />
        <x-form.input name="title_ar" label="{{ __('admin.page_builder.config_forms.title_ar') }}" :value="$config['title_ar'] ?? ''" dir="rtl" />
    </div>

    <div class="mt-3">
        <x-form.async-select name="vendor_listing_id"
            label="{{ __('admin.page_builder.config_forms.deal_of_day.vendor_listing') }}"
            search-url="{{ route('admin.page-builder.search.vendor-listings') }}"
            :value="$config['vendor_listing_id'] ?? ''"
            :value-label="$config['vendor_listing_label'] ?? null"
            :min-length="0" required
            help-text="{{ __('admin.page_builder.config_forms.deal_of_day.vendor_listing_help') }}" />
    </div>

    <x-form.date-picker name="ends_at" label="{{ __('admin.page_builder.config_forms.deal_of_day.deal_ends_at') }}" enableTime :value="$config['ends_at'] ?? ''" class="mt-3" />

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
