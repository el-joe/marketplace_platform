{{-- Create new page modal --}}
<x-modal id="create-page-modal" :title="__('admin.page_builder_section.add_page')" size="md">
    <form id="create-page-form" class="px-6 py-4 space-y-4">
        @csrf

        <x-form.input name="name" :label="__('admin.page_builder_section.page_name')" required placeholder="e.g. Homepage – UAE" />

        <x-form.select name="page_type" :label="__('admin.page_builder_section.page_slug')" required placeholder="— Select —" :options="['home' => 'Home', 'category' => 'Category', 'brand' => 'Brand', 'landing' => 'Landing', 'campaign' => 'Campaign', 'custom' => 'Custom']" />

        <x-form.select name="country_id" :label="__('admin.name')" required placeholder="— Select —"
            :options="$countries->mapWithKeys(fn($c) => [$c->id => $c->name_en . ' (' . ($c->site_code ?? '—') . ')'])" />

        <x-form.slug-input name="slug" :label="__('admin.page_builder_section.page_slug')" :editable="true" prefix="/p/" required />

        <x-form.input name="reference_id" label="Reference ID (optional)" placeholder="Category / Brand / Seller UUID"
            helpText="Used when the page targets a specific entity." />
    </form>

    <div class="px-6 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 rounded-b-lg">
        <button type="button" data-modal-close
            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
            {{ __('admin.cancel') }}
        </button>
        <button type="submit" form="create-page-form"
            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
            {{ __('admin.page_builder_section.add_page') }}
        </button>
    </div>
</x-modal>
