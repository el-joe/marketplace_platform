{{-- Create new page modal --}}
<x-modal id="create-page-modal" title="{{ __('admin.page_builder.create_page_modal.title') }}" size="md">
    <form id="create-page-form" class="px-6 py-4 space-y-4">
        @csrf

        <x-form.input name="name" label="{{ __('admin.page_builder.create_page_modal.page_name') }}" required placeholder="{{ __('admin.page_builder.create_page_modal.page_name_placeholder') }}" />

        <x-form.select name="page_type" label="{{ __('admin.page_builder.create_page_modal.page_type') }}" required placeholder="{{ __('admin.page_builder.create_page_modal.select_placeholder') }}" :options="[
            'home' => __('admin.page_builder.create_page_modal.types.home'),
            'category' => __('admin.page_builder.create_page_modal.types.category'),
            'brand' => __('admin.page_builder.create_page_modal.types.brand'),
            'landing' => __('admin.page_builder.create_page_modal.types.landing'),
            'campaign' => __('admin.page_builder.create_page_modal.types.campaign'),
            'custom' => __('admin.page_builder.create_page_modal.types.custom'),
        ]" />

        <x-form.select name="country_id" label="{{ __('common.country') }}" required placeholder="{{ __('admin.page_builder.create_page_modal.select_placeholder') }}"
            :options="$countries->mapWithKeys(fn($c) => [$c->id => $c->name_en . ' (' . ($c->site_code ?? '—') . ')'])" />

        <x-form.slug-input name="slug" label="{{ __('admin.page_builder.create_page_modal.slug') }}" :editable="true" prefix="/p/" required />

        <x-form.input name="reference_id" label="{{ __('admin.page_builder.create_page_modal.reference_id') }}" placeholder="{{ __('admin.page_builder.create_page_modal.reference_id_placeholder') }}"
            helpText="{{ __('admin.page_builder.create_page_modal.reference_id_help') }}" />
    </form>

    <div class="px-6 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 rounded-b-lg">
        <button type="button" data-modal-close
            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
            {{ __('common.cancel') }}
        </button>
        <button type="submit" form="create-page-form"
            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
            {{ __('admin.page_builder.create_page_modal.create_page') }}
        </button>
    </div>
</x-modal>