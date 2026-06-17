@extends('layouts.admin')

@section('title', 'Contract Templates')

@section('content')
<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Contract Templates</h1>
        <a href="{{ route('admin.classifieds.contract-templates.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium">
            <x-heroicon name="plus" class="w-4 h-4" />
            New Template
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Name</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Category</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Version</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Active</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Created</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($templates as $tpl)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $tpl->name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ $tpl->category?->name_en ?? 'General' }}</td>
                    <td class="px-4 py-3 text-center text-gray-500">v{{ $tpl->version }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block w-2 h-2 rounded-full {{ $tpl->is_active ? 'bg-emerald-400' : 'bg-gray-300' }}"></span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $tpl->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3 text-end">
                        <a href="{{ route('admin.classifieds.contract-templates.edit', $tpl) }}"
                           class="text-xs text-primary-600 font-medium hover:underline">Edit</a>
                        <form method="POST" action="{{ route('admin.classifieds.contract-templates.destroy', $tpl) }}"
                              class="inline ms-2" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">No templates yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
