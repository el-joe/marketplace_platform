@extends('layouts.admin')

@section('title', 'Nawy Now Sidebar Categories')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" defer></script>
@endpush

@section('content')
<div class="p-6 space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Nawy Now Sidebar Categories</h1>
            <p class="text-sm text-gray-500 mt-0.5">Drag to reorder, set an icon, and pin categories to the top of the Nawy Now sidebar. Only categories with at least one active listing appear here.</p>
        </div>
        <a href="{{ route('admin.admin-product-listings.index') }}" class="text-sm text-primary-600 hover:underline">&larr; Back to Listings</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-8 px-3 py-3"></th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">Category</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Products</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Nawy Icon</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Featured</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-700">Sort Order</th>
                    </tr>
                </thead>
                <tbody id="nawy-category-tree" class="divide-y divide-gray-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50 category-row" data-id="{{ $category->id }}">
                            <td class="px-3 py-3 text-gray-300 cursor-grab drag-handle">
                                <x-heroicon name="bars-3" class="w-4 h-4" />
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-gray-900">{{ $category->name_en }}</span>
                                <span class="block text-xs text-gray-400" dir="rtl">{{ $category->name_ar }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-500">{{ $category->product_count }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <img class="nawy-icon-preview w-8 h-8 rounded object-cover border border-gray-200 {{ $category->nawy_icon_path ? '' : 'hidden' }}"
                                         src="{{ $category->nawy_icon_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($category->nawy_icon_path) : '' }}"
                                         alt="">
                                    <input type="file" accept="image/*"
                                           class="nawy-icon-input text-xs"
                                           data-upload-url="{{ route('admin.admin-product-listings.categories.icon', $category->id) }}">
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" dir="ltr"
                                        class="nawy-featured-toggle relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none {{ $category->nawy_is_featured ? 'bg-primary-600' : 'bg-gray-200' }}"
                                        data-url="{{ route('admin.admin-product-listings.categories.toggle-featured', $category->id) }}"
                                        aria-label="Toggle featured">
                                    <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform {{ $category->nawy_is_featured ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-400 text-xs nawy-sort-order">{{ $category->nawy_sort_order }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">No categories with active Nawy Now listings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Drag-and-drop reorder
    const tree = document.getElementById('nawy-category-tree');
    if (typeof Sortable !== 'undefined' && tree) {
        Sortable.create(tree, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: async () => {
                const ids = [...tree.querySelectorAll('tr.category-row[data-id]')].map(tr => tr.dataset.id);
                tree.querySelectorAll('tr.category-row').forEach((tr, i) => {
                    const cell = tr.querySelector('.nawy-sort-order');
                    if (cell) cell.textContent = i;
                });
                try {
                    const res = await fetch('{{ route("admin.admin-product-listings.categories.reorder") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ ids }),
                    });
                    if (!res.ok) throw new Error();
                    window.Toast && window.Toast.success('Order saved.');
                } catch (e) {
                    window.Toast && window.Toast.error('Failed to save order.');
                }
            },
        });
    }

    // Featured toggle
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.nawy-featured-toggle');
        if (!btn) return;
        btn.disabled = true;
        fetch(btn.dataset.url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
            .then(res => res.json())
            .then(res => {
                btn.classList.toggle('bg-primary-600', res.nawy_is_featured);
                btn.classList.toggle('bg-gray-200', !res.nawy_is_featured);
                const knob = btn.querySelector('span');
                knob.classList.toggle('translate-x-4', res.nawy_is_featured);
                knob.classList.toggle('translate-x-0.5', !res.nawy_is_featured);
            })
            .catch(() => window.Toast && window.Toast.error('Failed to update.'))
            .finally(() => { btn.disabled = false; });
    });

    // Icon upload (inline, per-row)
    document.querySelectorAll('.nawy-icon-input').forEach(function (input) {
        input.addEventListener('change', function () {
            const file = input.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('icon', file);

            fetch(input.dataset.uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: formData,
            })
                .then(res => res.json())
                .then(res => {
                    if (!res.success) throw new Error();
                    const preview = input.closest('td').querySelector('.nawy-icon-preview');
                    preview.src = res.icon_url;
                    preview.classList.remove('hidden');
                    window.Toast && window.Toast.success('Icon updated.');
                })
                .catch(() => window.Toast && window.Toast.error('Failed to upload icon.'));
        });
    });
});
</script>
@endpush
