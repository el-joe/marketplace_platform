@extends('layouts.admin')

@section('title', 'Travel Packages')

@section('content')
<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Travel Packages</h1>
            @if($pendingCount > 0)
            <p class="text-sm text-amber-600 font-medium mt-0.5">{{ $pendingCount }} package(s) pending review</p>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Search by title..."
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-64">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All Statuses</option>
            @foreach(['draft','pending_review','active','sold_out','expired'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <input name="country" value="{{ request('country') }}" placeholder="Destination country..."
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-48">
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
        <a href="{{ route('admin.travel.packages.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Package</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Agency</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Destination</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Departure</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Price</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($packages as $pkg)
                @php
                $statusColors = ['draft'=>'bg-gray-100 text-gray-600','pending_review'=>'bg-amber-100 text-amber-700','active'=>'bg-emerald-100 text-emerald-700','sold_out'=>'bg-purple-100 text-purple-700','expired'=>'bg-gray-100 text-gray-500'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900">{{ $pkg->title_en }}</p>
                        <p class="text-xs text-gray-400">{{ $pkg->title_ar }}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->agency?->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->destination_country }}{{ $pkg->destination_city ? ', '.$pkg->destination_city : '' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->departure_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $pkg->priceFormatted() }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$pkg->status] ?? '' }}">
                            {{ ucfirst(str_replace('_',' ',$pkg->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.travel.packages.show', $pkg) }}" class="text-primary-600 text-xs font-medium hover:underline">Review</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">No packages found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $packages->links() }}
        </div>
    </div>
</div>
@endsection
