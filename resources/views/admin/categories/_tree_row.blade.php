@php $hasChildren = $category->children->isNotEmpty(); @endphp
<tr class="hover:bg-gray-50 transition-colors" data-id="{{ $category->id }}"
    data-parent="{{ $category->parent_id ?? '' }}" data-depth="{{ $depth }}" @if($depth > 0) style="display:none"
    @endif>

    {{-- Expand toggle --}}
    <td class="px-2 py-2 w-8">
        @if($hasChildren)
            <button type="button" onclick="window.catTree && window.catTree.toggle('{{ $category->id }}')"
                class="expand-btn w-6 h-6 flex items-center justify-center text-gray-400 hover:text-gray-600 rounded"
                data-id="{{ $category->id }}">
                <x-heroicon name="chevron-right" class="w-4 h-4 transition-transform duration-150" />
            </button>
        @endif
    </td>

    {{-- Name with indent --}}
    <td class="px-4 py-2">
        <div class="flex items-center gap-2" style="padding-left: {{ $depth * 20 }}px">
            <input type="checkbox" class="category-cb rounded border-gray-300 text-primary-600"
                value="{{ $category->id }}" />
            <div>
                <span class="font-medium text-gray-900">{{ $category->name_en }}</span>
                @if($category->name_ar)
                    <span class="text-xs text-gray-400 mx-1" dir="rtl">{{ $category->name_ar }}</span>
                @endif
                @if($category->slug)
                    <span class="text-xs text-gray-300">/{{ $category->slug }}</span>
                @endif
            </div>
        </div>
    </td>

    <td class="px-4 py-2 text-right text-gray-600 text-sm">
        {{ number_format($category->product_count ?? 0) }}
    </td>

    <td class="px-4 py-2 text-right text-gray-600 text-sm">
        {{ number_format((float) $category->commission_rate, 2) }}%
    </td>

    <td class="px-4 py-2">
        @if($category->is_active && $category->is_visible)
            <x-badge color="success">Active</x-badge>
        @elseif($category->is_active && !$category->is_visible)
            <x-badge color="warning">Hidden</x-badge>
        @else
            <x-badge color="gray">Inactive</x-badge>
        @endif
    </td>

    <td class="px-4 py-2">
        <button type="button"
            class="featured-btn flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full transition-colors
                {{ $category->is_featured ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500 hover:bg-amber-50 hover:text-amber-600' }}"
            data-id="{{ $category->id }}" data-featured="{{ $category->is_featured ? '1' : '0' }}"
            data-url="{{ route('admin.categories.toggle-featured', $category->id) }}">
            <x-heroicon name="star" class="w-3.5 h-3.5" />
            <span>{{ $category->is_featured ? 'Featured' : 'Add' }}</span>
        </button>
    </td>

    <td class="px-4 py-2 text-right">
        <div class="flex items-center justify-end gap-1">
            <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-ghost btn-xs">Edit</a>
            <button type="button" class="btn btn-ghost btn-xs text-red-600 hover:bg-red-50 delete-cat-btn"
                data-id="{{ $category->id }}" data-name="{{ addslashes($category->name_en) }}"
                data-url="{{ route('admin.categories.destroy', $category->id) }}">
                Delete
            </button>
        </div>
    </td>
</tr>

@if($hasChildren)
    @foreach($category->children as $child)
        @include('admin.categories._tree_row', ['category' => $child, 'depth' => $depth + 1])
    @endforeach
@endif