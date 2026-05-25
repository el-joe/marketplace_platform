@extends('layouts.admin')

@section('title', 'Categories')

@push('styles')
    @vite(['resources/js/admin/categories.js'])
@endpush

@section('content')
    <div class="space-y-4">

        {{-- Toolbar --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button type="button" id="bulk-commission-btn" class="btn btn-ghost btn-sm hidden">
                    <x-heroicon name="percent-badge" class="w-4 h-4 mr-1" />
                    Set Commission
                </button>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                <x-heroicon name="plus" class="w-4 h-4 mr-1" />
                New Category
            </a>
        </div>

        {{-- Tree Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm" id="categories-table">
                <thead>
                    <tr
                        class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-2 py-3 w-8"></th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3 text-right">Products</th>
                        <th class="px-4 py-3 text-right">Commission</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Featured</th>
                        <th class="px-4 py-3 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($roots as $root)
                        @include('admin.categories._tree_row', ['category' => $root, 'depth' => 0])
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                No categories yet.
                                <a href="{{ route('admin.categories.create') }}" class="text-primary-600 underline ml-1">Add the
                                    first one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Bulk Commission Modal --}}
        <div id="bulk-commission-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" id="bulk-commission-backdrop"></div>
            <div class="relative bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full space-y-4">
                <h3 class="font-semibold text-gray-900">Bulk Commission Rate</h3>
                <p class="text-sm text-gray-500">Apply a new rate to <span id="bulk-count">0</span> selected categories.</p>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Commission Rate (%)</label>
                    <input type="number" id="bulk-rate-input" min="0" max="100" step="0.01" class="input w-full"
                        placeholder="e.g. 10.00" />
                </div>
                <div class="flex gap-2">
                    <button type="button" id="bulk-commission-cancel" class="btn btn-ghost flex-1">Cancel</button>
                    <button type="button" id="bulk-commission-apply" class="btn btn-primary flex-1">Apply</button>
                </div>
            </div>
        </div>

    </div>

    <script>
        window.ROUTES_CAT = {
            bulkCommission: '{{ route('admin.categories.bulk-commission') }}',
            toggleFeatured: '{{ url('categories') }}/:id/toggle-featured',
            destroy: '{{ url('categories') }}/:id',
        };
    </script>
@endsection