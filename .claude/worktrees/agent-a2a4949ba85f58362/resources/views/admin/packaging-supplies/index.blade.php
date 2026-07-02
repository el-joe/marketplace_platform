@extends('layouts.admin')

@section('title', 'Packaging Supplies')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Packaging Supplies</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage the catalog of boxes, bags, tape, and labels available to vendors.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.packaging-supplies.requests') }}" class="btn btn-secondary">Requests Queue</a>
            <a href="{{ route('admin.packaging-supplies.create') }}" class="btn btn-primary">+ Add Supply</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">Name</th>
                    <th class="th">Type</th>
                    <th class="th">Size</th>
                    <th class="th">Unit Cost</th>
                    <th class="th">Stock</th>
                    <th class="th">Status</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($supplies as $supply)
                    <tr class="hover:bg-gray-50">
                        <td class="td">
                            <div class="font-medium text-gray-900">{{ $supply->name_en }}</div>
                            <div class="text-xs text-gray-400 font-arabic">{{ $supply->name_ar }}</div>
                        </td>
                        <td class="td">
                            <span class="badge {{ $supply->typeBadgeClass() }}">{{ ucfirst($supply->type) }}</span>
                        </td>
                        <td class="td text-gray-500">{{ $supply->size ?? '—' }}</td>
                        <td class="td font-medium">{{ $supply->unit_cost_formatted }}</td>
                        <td class="td text-gray-600">{{ $supply->stock_available !== null ? number_format($supply->stock_available) : '∞' }}</td>
                        <td class="td">
                            @if($supply->is_active)
                                <span class="badge bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="badge bg-gray-100 text-gray-600">Inactive</span>
                            @endif
                        </td>
                        <td class="td text-right space-x-2">
                            <a href="{{ route('admin.packaging-supplies.edit', $supply) }}"
                               class="text-primary-600 hover:underline text-xs font-medium">Edit</a>
                            <form method="POST" action="{{ route('admin.packaging-supplies.destroy', $supply) }}"
                                  class="inline" onsubmit="return confirm('Delete this supply?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline text-xs font-medium">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="td text-center text-gray-400 py-10">No supplies yet. Add one to get started.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $supplies->links() }}</div>

@endsection
