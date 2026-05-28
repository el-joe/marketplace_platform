{{-- Add / edit slide modal (for hero_slider blocks) --}}
<x-modal id="slide-modal" title="Slide" size="lg">
    <form id="slide-form" class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
        @csrf
        <input type="hidden" name="id" id="slide-id">
        <input type="hidden" name="page_block_id" id="slide-block-id">

        <div class="grid grid-cols-2 gap-4">
            <x-form.input name="title_en" label="Title (EN)" />
            <x-form.input name="title_ar" label="Title (AR)" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-form.textarea name="subtitle_en" label="Subtitle (EN)" rows="2" />
            <x-form.textarea name="subtitle_ar" label="Subtitle (AR)" rows="2" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-form.input name="cta_label_en" label="CTA label (EN)" />
            <x-form.input name="cta_label_ar" label="CTA label (AR)" />
        </div>

        <x-form.input name="cta_url" label="CTA URL" placeholder="/products/x or https://…" />

        <div class="grid grid-cols-3 gap-4">
            <x-form.select name="text_position" label="Text position">
                <option value="left">Left</option>
                <option value="center">Center</option>
                <option value="right">Right</option>
            </x-form.select>
            <x-form.input name="text_color" type="color" label="Text color" value="#ffffff" />
            <x-form.input name="overlay_opacity" type="number" label="Overlay opacity" value="0.30" step="0.05" min="0" max="1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-form.date-picker name="visible_from" label="Visible from" enableTime />
            <x-form.date-picker name="visible_until" label="Visible until" enableTime />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-form.input name="desktop_file_id" type="number" label="Desktop image (file ID)" helpText="Upload via Files first; paste ID." />
            <x-form.input name="mobile_file_id" type="number" label="Mobile image (file ID)" />
        </div>

        <x-form.toggle name="is_active" label="Active" :value="true" />
        <x-form.toggle name="cta_open_new_tab" label="Open CTA in new tab" />
    </form>

    <div class="px-6 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 rounded-b-lg">
        <button type="button" data-modal-close
                class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
            Cancel
        </button>
        <button type="submit" form="slide-form"
                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
            Save slide
        </button>
    </div>
</x-modal>
