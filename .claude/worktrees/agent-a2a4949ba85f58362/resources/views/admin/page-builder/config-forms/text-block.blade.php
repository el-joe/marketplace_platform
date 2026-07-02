@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <x-form.textarea name="content_html_en" label="Content (EN)"  rows="6" :value="$config['content_html_en'] ?? ''" />
    <x-form.textarea name="content_html_ar" label="Content (AR)"  rows="6" :value="$config['content_html_ar'] ?? ''" />

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.select name="text_align" label="Text align" :value="$config['text_align'] ?? 'left'">
            <option value="left">Left</option>
            <option value="center">Center</option>
            <option value="right">Right</option>
            <option value="justify">Justify</option>
        </x-form.select>
        <x-form.input name="max_width" label="Max width" :value="$config['max_width'] ?? '1200px'" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
