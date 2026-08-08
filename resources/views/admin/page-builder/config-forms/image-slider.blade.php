@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" dir="ltr" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" dir="rtl" />
    </div>

    {{-- Grid layout --}}
    <div class="grid grid-cols-2 gap-3 mt-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Columns per row</label>
            <select name="columns" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                @foreach([2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7', 8 => '8'] as $val => $label)
                    <option value="{{ $val }}" {{ ($config['columns'] ?? 5) == $val ? 'selected' : '' }}>
                        {{ $label }} columns
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Rows visible</label>
            <select name="rows" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                <option value="1" {{ ($config['rows'] ?? 1) == 1 ? 'selected' : '' }}>1 row</option>
                <option value="2" {{ ($config['rows'] ?? 1) == 2 ? 'selected' : '' }}>2 rows</option>
                <option value="3" {{ ($config['rows'] ?? 1) == 3 ? 'selected' : '' }}>3 rows</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Image shape</label>
            <select name="image_shape" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                <option value="rounded" {{ ($config['image_shape'] ?? 'rounded') === 'rounded' ? 'selected' : '' }}>Rounded rectangle</option>
                <option value="circle"  {{ ($config['image_shape'] ?? 'rounded') === 'circle'  ? 'selected' : '' }}>Circle</option>
                <option value="square"  {{ ($config['image_shape'] ?? 'rounded') === 'square'  ? 'selected' : '' }}>Square</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Aspect ratio</label>
            <select name="aspect_ratio" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                @foreach(['1:1' => '1:1 — Square', '4:3' => '4:3 — Landscape', '3:4' => '3:4 — Portrait', '16:9' => '16:9 — Wide', '3:2' => '3:2'] as $val => $label)
                    <option value="{{ $val }}" {{ ($config['aspect_ratio'] ?? '1:1') === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Toggles --}}
    <div class="flex flex-wrap gap-4 mt-3">
        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="scrollable" value="1"
                {{ ($config['scrollable'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300">
            Horizontal scroll (slider)
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="show_label" value="1"
                {{ ($config['show_label'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300">
            Show label below image
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="show_badge" value="1"
                {{ ($config['show_badge'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300">
            Show badge on image
        </label>
    </div>

    {{-- Images list --}}
    <div data-ad-images-panel data-block-id="{{ $block?->id }}" class="mt-4"></div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
