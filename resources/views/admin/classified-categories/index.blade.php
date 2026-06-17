@extends('layouts.admin')

@section('title', 'Classified Categories')

@section('content')
<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Classified Categories</h1>
        <a href="{{ route('admin.classifieds.categories.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium">
            <x-heroicon name="plus" class="w-4 h-4" />
            New Category
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Name</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Slug</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Parent</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Contract</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Map</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Sketch</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Active</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $cat)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">
                        {{ $cat->name_en }}
                        <span class="block text-xs text-gray-400">{{ $cat->name_ar }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $cat->slug }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $cat->parent?->name_en ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $cat->contractTemplate?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($cat->requires_location_map)
                            <span class="text-emerald-600">✓</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($cat->requires_sketch_upload)
                            <span class="text-emerald-600">✓</span>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block w-2 h-2 rounded-full {{ $cat->is_active ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                    </td>
                    <td class="px-4 py-3 text-end">
                        <a href="{{ route('admin.classifieds.categories.edit', $cat) }}"
                           class="text-xs text-primary-600 font-medium hover:underline">Edit</a>
                        <form method="POST" action="{{ route('admin.classifieds.categories.destroy', $cat) }}"
                              class="inline ms-2" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">No categories yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
