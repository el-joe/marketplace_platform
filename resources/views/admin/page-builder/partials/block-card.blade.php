@php
    $typeColors = [
        'hero_slider' => 'primary',
        'ad_images_2col' => 'purple',
        'ad_images_3col' => 'purple',
        'ad_images_4col' => 'purple',
        'split_banner' => 'purple',
        'product_grid' => 'success',
        'product_row' => 'success',
        'category_grid' => 'warning',
        'html_block' => 'gray',
        'countdown_timer' => 'danger',
    ];
    $typeColor = $typeColors[$block->block_type] ?? 'gray';

    $subItems = match ($block->block_type) {
        'hero_slider' => $block->slides->count() . ' slide(s)',
        'ad_images_2col', 'ad_images_3col', 'ad_images_4col', 'split_banner' => $block->adImageItems->count() . ' image(s)',
        'product_grid', 'product_row' => $block->blockProducts->count() . ' product(s)',
        default => null,
    };
@endphp
<div class="block-card group relative bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow"
    data-block-id="{{ $block->id }}" data-block-type="{{ $block->block_type }}">

    {{-- Drag handle --}}
    <div
        class="drag-handle absolute left-2 top-1/2 -translate-y-1/2 cursor-grab text-gray-300 hover:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity">
        <x-heroicon name="bars-2" class="w-4 h-4" />
    </div>

    <div class="pl-8 pr-4 py-3 flex items-center gap-3">

        {{-- Type badge --}}
        <x-badge :color="$typeColor" class="flex-shrink-0 text-[10px] uppercase tracking-wider">
            {{ $block->block_type }}
        </x-badge>

        {{-- Summary --}}
        <div class="flex-1 min-w-0 text-sm">
            @if(!empty($block->config['title_en']))
                <span class="font-medium text-gray-800 truncate block">{{ $block->config['title_en'] }}</span>
            @endif
            @if($subItems)
                <span class="text-xs text-gray-400">{{ $subItems }}</span>
            @endif
        </div>

        {{-- Visibility indicator --}}
        <span class="flex-shrink-0 text-xs {{ $block->is_visible ? 'text-success-600' : 'text-gray-300' }}"
            title="{{ $block->is_visible ? 'Visible' : 'Hidden' }}">
            <x-heroicon :name="$block->is_visible ? 'eye' : 'eye-slash'" class="w-4 h-4" />
        </span>

        {{-- Device target --}}
        @if($block->device_target !== 'all')
            <span class="flex-shrink-0 text-[10px] text-gray-400 bg-gray-100 rounded px-1.5 py-0.5">
                {{ $block->device_target }}
            </span>
        @endif

        {{-- Actions --}}
        <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
            <button type="button" class="btn-block-toggle btn btn-ghost btn-xs" data-block-id="{{ $block->id }}"
                title="Toggle visibility">
                <x-heroicon :name="$block->is_visible ? 'eye-slash' : 'eye'" class="w-3.5 h-3.5" />
            </button>
            <button type="button" class="btn-block-configure btn btn-secondary btn-xs" data-block-id="{{ $block->id }}"
                data-block-type="{{ $block->block_type }}">
                Configure
            </button>
            <button type="button" class="btn-block-revisions btn btn-ghost btn-xs" data-block-id="{{ $block->id }}"
                title="History">
                <x-heroicon name="clock" class="w-3.5 h-3.5" />
            </button>
            <button type="button" class="btn-block-delete btn btn-danger btn-xs" data-block-id="{{ $block->id }}"
                title="Delete block">
                <x-heroicon name="trash" class="w-3.5 h-3.5" />
            </button>
        </div>

    </div>
</div>