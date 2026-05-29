@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="max_items" type="number" label="Max products" :value="$config['max_items'] ?? 8" min="1"
            max="30" />
        <x-form.input name="items_per_row" type="number" label="Items per row" :value="$config['items_per_row'] ?? 4"
            min="1" max="8" />
    </div>

    <x-form.select name="source" label="Ad source" :value="$config['source'] ?? 'active_campaigns'" class="mt-3">
        <option value="active_campaigns">Active ad campaigns</option>
        <option value="manual">Manual selection</option>
    </x-form.select>

    <div class="grid grid-cols-3 gap-3 mt-3">
        <x-form.toggle name="show_sponsored_badge" label="Sponsored badge" :value="$config['show_sponsored_badge'] ?? true" />
        <x-form.toggle name="scrollable_row" label="Scrollable" :value="$config['scrollable_row'] ?? false" />
        <x-form.toggle name="show_ratings" label="Ratings" :value="$config['show_ratings'] ?? true" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>