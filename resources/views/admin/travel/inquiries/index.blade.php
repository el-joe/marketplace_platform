@extends('layouts.admin')

@section('title', 'Travel Package Inquiries')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Travel Package Inquiries</h1>
            <p class="text-sm text-gray-500 mt-0.5">Read-only lead volume overview across all agencies.</p>
        </div>
    </div>

    {{-- Filters --}}
    @php
    $statuses = ['' => 'All', 'new' => 'New', 'contacted' => 'Contacted', 'converted' => 'Converted', 'closed' => 'Closed'];
    $currentStatus = request('status', '');
    @endphp
    <div class="flex gap-2 flex-wrap">
        @foreach($statuses as $val => $label)
        <a href="{{ route('admin.travel.inquiries.index', array_filter(['status' => $val])) }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors
                  {{ $currentStatus === $val ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Name</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Phone</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Package</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Agency</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Travelers</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Submitted</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($inquiries as $inq)
                @php
                $colors = ['new'=>'bg-blue-100 text-blue-700','contacted'=>'bg-amber-100 text-amber-700','converted'=>'bg-emerald-100 text-emerald-700','closed'=>'bg-gray-100 text-gray-500'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900">{{ $inq->name }}</p>
                        @if($inq->email)
                        <p class="text-xs text-gray-400">{{ $inq->email }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $inq->phone }}</td>
                    <td class="px-4 py-3 text-gray-700 text-xs">{{ $inq->package->title_en ?: $inq->package->title_ar }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $inq->package->agency->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ $inq->travelers_count ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$inq->status] ?? '' }}">
                            {{ $inq->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $inq->created_at->format('d M Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">No inquiries yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $inquiries->links() }}
        </div>
    </div>
</div>
@endsection
