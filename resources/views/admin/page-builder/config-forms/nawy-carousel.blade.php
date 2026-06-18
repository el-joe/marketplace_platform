@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" placeholder="Now Nawy" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" placeholder="كاروسيل Now Nawy" />
    </div>

    <x-form.select name="fulfillment_filter" label="Fulfillment filter" :value="$config['fulfillment_filter'] ?? 'all'" class="mt-3">
        <option value="all">All types</option>
        <option value="express">Express only 🚀</option>
        <option value="global">Global only 🌍</option>
    </x-form.select>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="category_id" label="Category ID (optional)" :value="$config['category_id'] ?? ''" placeholder="Leave blank for all" />
        <x-form.input name="limit" type="number" label="Max items" :value="$config['limit'] ?? 10" min="1" max="50" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
