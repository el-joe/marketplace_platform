@php
    /** @var \App\Models\PageBlock|null $block */
    $tiles = $config['tiles'] ?? [];
@endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" dir="ltr" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" dir="rtl" />
    </div>

    <x-form.select name="columns" label="Grid columns" :value="$config['columns'] ?? 2"
        :options="['2' => '2 columns', '3' => '3 columns', '4' => '4 columns']" class="mt-3" />

    <div class="mt-4">
        <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-gray-700">Tiles</label>
            <button type="button" id="add-promo-tile" class="text-xs text-primary-600">+ Add Tile</button>
        </div>
        <div id="promo-tiles-list" class="space-y-2">
            @foreach($tiles as $i => $tile)
                <div class="tile-row p-3 border border-gray-200 rounded-lg bg-gray-50 space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="tiles[{{ $i }}][label_en]"
                            value="{{ $tile['label_en'] ?? '' }}" placeholder="Label (EN)" dir="ltr"
                            class="text-sm border border-gray-300 rounded px-2 py-1">
                        <input type="text" name="tiles[{{ $i }}][label_ar]"
                            value="{{ $tile['label_ar'] ?? '' }}" placeholder="التسمية" dir="rtl"
                            class="text-sm border border-gray-300 rounded px-2 py-1">
                    </div>
                    <input type="url" name="tiles[{{ $i }}][image_url]"
                        value="{{ $tile['image_url'] ?? '' }}" placeholder="Image URL"
                        class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                    <input type="text" name="tiles[{{ $i }}][link_url]"
                        value="{{ $tile['link_url'] ?? '' }}" placeholder="Link URL (e.g. /browse/product/cat-id)"
                        class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="tiles[{{ $i }}][badge_label_en]"
                            value="{{ $tile['badge_label_en'] ?? '' }}" placeholder="Badge (EN)"
                            class="text-sm border border-gray-300 rounded px-2 py-1">
                        <input type="text" name="tiles[{{ $i }}][badge_label_ar]"
                            value="{{ $tile['badge_label_ar'] ?? '' }}" placeholder="الشارة"
                            class="text-sm border border-gray-300 rounded px-2 py-1">
                    </div>
                    <button type="button" class="text-xs text-rose-500 remove-tile">Remove tile</button>
                </div>
            @endforeach
        </div>
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])

    <script>
    (function() {
        let idx = {{ count($tiles) }};
        document.getElementById('add-promo-tile')?.addEventListener('click', function() {
            const row = document.createElement('div');
            row.className = 'tile-row p-3 border border-gray-200 rounded-lg bg-gray-50 space-y-2';
            row.innerHTML = `
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="tiles[${idx}][label_en]" placeholder="Label (EN)" dir="ltr" class="text-sm border border-gray-300 rounded px-2 py-1">
                    <input type="text" name="tiles[${idx}][label_ar]" placeholder="التسمية" dir="rtl" class="text-sm border border-gray-300 rounded px-2 py-1">
                </div>
                <input type="url" name="tiles[${idx}][image_url]" placeholder="Image URL" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                <input type="text" name="tiles[${idx}][link_url]" placeholder="Link URL" class="w-full text-sm border border-gray-300 rounded px-2 py-1">
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="tiles[${idx}][badge_label_en]" placeholder="Badge (EN)" class="text-sm border border-gray-300 rounded px-2 py-1">
                    <input type="text" name="tiles[${idx}][badge_label_ar]" placeholder="الشارة" class="text-sm border border-gray-300 rounded px-2 py-1">
                </div>
                <button type="button" class="text-xs text-rose-500 remove-tile">Remove tile</button>
            `;
            document.getElementById('promo-tiles-list').appendChild(row);
            idx++;
        });
        document.getElementById('promo-tiles-list')?.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-tile')) e.target.closest('.tile-row').remove();
        });
    })();
    </script>
</form>
