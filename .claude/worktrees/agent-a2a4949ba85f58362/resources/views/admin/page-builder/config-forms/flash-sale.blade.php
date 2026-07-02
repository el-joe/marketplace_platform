@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <div class="space-y-1">
        <label class="block text-sm font-medium text-gray-700">Flash sale</label>
        <select name="flash_sale_id" required class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-200 focus:border-primary-500">
            <option value="">— select —</option>
            @foreach ($flashSales as $fs)
                <option value="{{ $fs->id }}" {{ ($config['flash_sale_id'] ?? '') == $fs->id ? 'selected' : '' }}>
                    {{ $fs->name_en }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="max_items_shown" type="number" label="Max items" :value="$config['max_items_shown'] ?? 8" min="1" />
        <x-form.input name="items_per_row"   type="number" label="Per row"   :value="$config['items_per_row']   ?? 4" min="1" max="8" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.toggle name="show_countdown" label="Show countdown" :value="$config['show_countdown'] ?? true" />
        <x-form.toggle name="show_stock_bar" label="Show stock bar" :value="$config['show_stock_bar'] ?? true" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="badge_label_en" label="Badge label (EN)" :value="$config['badge_label_en'] ?? ''" />
        <x-form.input name="badge_label_ar" label="Badge label (AR)" :value="$config['badge_label_ar'] ?? ''" />
    </div>

    <x-form.input name="background_color" type="color" label="Background color" :value="$config['background_color'] ?? '#fef3c7'" class="mt-3" />

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
