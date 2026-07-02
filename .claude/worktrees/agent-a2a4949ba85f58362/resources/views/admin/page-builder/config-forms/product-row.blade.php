@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <x-form.select name="source" label="Source" :value="$config['source'] ?? 'best_sellers'" class="mt-3">
        <option value="best_sellers">Best sellers</option>
        <option value="new_arrivals">New arrivals</option>
        <option value="top_rated">Top rated</option>
        <option value="trending">Trending</option>
        <option value="category">From category</option>
        <option value="flash_sale">From flash sale</option>
        <option value="manual">Manual selection</option>
        <option value="personalized">Personalized</option>
    </x-form.select>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Category</label>
            <select name="category_id" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                <option value="">— none —</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" {{ ($config['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name_en }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="space-y-1">
            <label class="block text-sm font-medium text-gray-700">Flash sale</label>
            <select name="flash_sale_id" class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
                <option value="">— none —</option>
                @foreach ($flashSales as $fs)
                    <option value="{{ $fs->id }}" {{ ($config['flash_sale_id'] ?? '') == $fs->id ? 'selected' : '' }}>
                        {{ $fs->name_en }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="items_per_row" type="number" label="Items per row" :value="$config['items_per_row'] ?? 4" min="1" max="8" />
        <x-form.input name="max_products"  type="number" label="Max products"  :value="$config['max_products']  ?? 12" min="1" max="50" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.toggle name="show_view_all"  label="View-all link" :value="$config['show_view_all'] ?? true" />
        <x-form.toggle name="scrollable_row" label="Scrollable"    :value="$config['scrollable_row'] ?? false" />
    </div>
    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.toggle name="show_ratings" label="Ratings" :value="$config['show_ratings'] ?? true" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
