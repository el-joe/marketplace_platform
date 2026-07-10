@extends('layouts.admin')

@section('title', 'Portal Content')

@section('content')
<div class="p-6 space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Portal Content</h1>
        <p class="text-sm text-gray-500 mt-0.5">Edit the marketing text and links shown on the public portal pages (home, FAQ, nav, footer, etc.), in English and Arabic.</p>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">Page</th>
                    <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">Fields</th>
                    <th class="px-4 py-3 text-start text-xs font-semibold text-gray-500 uppercase tracking-wider">Last Updated</th>
                    <th class="px-4 py-3 text-end text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($pages as $page)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $page->page_key }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $page->fields_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $page->last_updated_at ? \Illuminate\Support\Carbon::parse($page->last_updated_at)->format('d M Y H:i') : '—' }}</td>
                        <td class="px-4 py-3 text-end">
                            <a href="{{ route('admin.portal-content.page', $page->page_key) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <x-heroicon name="pencil-square" class="w-4 h-4" />
                                Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-400">No portal content pages found. Run the PortalContentSeeder to seed initial pages.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
