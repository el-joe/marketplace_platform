@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <x-form.input name="max_items" type="number" label="Max items" :value="$config['max_items'] ?? 10" min="1" max="50"
        class="mt-3" />

    <x-form.select name="source" label="Source" :value="$config['source'] ?? 'auto'" class="mt-3">
        <option value="auto">Auto (from search analytics)</option>
        <option value="manual">Manual keywords</option>
    </x-form.select>

    <x-form.textarea name="manual_keywords" label="Manual keywords (one per line)" rows="4"
        :value="$config['manual_keywords'] ?? ''" class="mt-3" helpText="Only used when source is set to Manual." />

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.toggle name="show_category_filter" label="Category filter" :value="$config['show_category_filter'] ?? false" />
        <x-form.toggle name="show_icons" label="Show icons" :value="$config['show_icons'] ?? true" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>