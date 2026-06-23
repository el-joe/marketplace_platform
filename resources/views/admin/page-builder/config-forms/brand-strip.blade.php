@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="max_items"      type="number" label="Max brands"   :value="$config['max_items'] ?? 10" min="1" max="40" />
        <x-form.toggle name="show_logo_only" label="Logos only" :value="$config['show_logo_only'] ?? true" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
