@extends('layouts.admin')

@section('title', __('admin.packaging_supplies.title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.packaging_supplies.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.packaging_supplies.subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.packaging-supplies.requests') }}" class="btn btn-secondary">{{ __('admin.packaging_supplies.requests_queue') }}</a>
            <a href="{{ route('admin.packaging-supplies.create') }}" class="btn btn-primary">{{ __('admin.packaging_supplies.add_supply') }}</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('admin.packaging_supplies.name') }}</th>
                    <th class="th">{{ __('admin.packaging_supplies.type') }}</th>
                    <th class="th">{{ __('admin.packaging_supplies.size') }}</th>
                    <th class="th">{{ __('admin.packaging_supplies.unit_cost') }}</th>
                    <th class="th">{{ __('admin.packaging_supplies.stock') }}</th>
                    <th class="th">{{ __('admin.packaging_supplies.status') }}</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($supplies as $supply)
                    <tr class="hover:bg-gray-50">
                        <td class="td">
                            <div class="font-medium text-gray-900">{{ $supply->name_en }}</div>
                            <div class="text-xs text-gray-400 font-arabic" dir="rtl">{{ $supply->name_ar }}</div>
                        </td>
                        <td class="td">
                            <span class="badge {{ $supply->typeBadgeClass() }}">{{ ucfirst($supply->type) }}</span>
                        </td>
                        <td class="td text-gray-500">{{ $supply->size ?? '—' }}</td>
                        <td class="td font-medium">{{ $supply->unit_cost_formatted }}</td>
                        <td class="td text-gray-600">{{ $supply->stock_available !== null ? number_format($supply->stock_available) : '∞' }}</td>
                        <td class="td">
                            @if($supply->is_active)
                                <span class="badge bg-green-100 text-green-800">{{ __('admin.packaging_supplies.active') }}</span>
                            @else
                                <span class="badge bg-gray-100 text-gray-600">{{ __('admin.packaging_supplies.inactive') }}</span>
                            @endif
                        </td>
                        <td class="td text-end space-x-2">
                            <a href="{{ route('admin.packaging-supplies.edit', $supply) }}"
                               class="text-primary-600 hover:underline text-xs font-medium">{{ __('admin.packaging_supplies.edit') }}</a>
                            <form method="POST" action="{{ route('admin.packaging-supplies.destroy', $supply) }}"
                                  class="inline" onsubmit="return confirm('{{ __('admin.packaging_supplies.delete_supply_confirm') }}')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline text-xs font-medium">{{ __('admin.packaging_supplies.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="td text-center text-gray-400 py-10">{{ __('admin.packaging_supplies.no_supplies_yet') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $supplies->links() }}</div>

@endsection
