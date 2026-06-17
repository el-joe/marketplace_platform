@extends('layouts.admin')

@section('title', 'Classified Listings')

@section('content')
<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Classified Listings</h1>
            @if($pendingCount > 0)
            <p class="text-sm text-amber-600 font-medium mt-0.5">
                {{ $pendingCount }} listing(s) pending review
            </p>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Search by number / title..."
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-64">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All Statuses</option>
            @foreach(['draft','pending_contract','pending_review','active','paused','sold','expired','rejected'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <select name="category" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                {{ $cat->name_en }}
            </option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
        <a href="{{ route('admin.classifieds.listings.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Number</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Title</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Customer</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Category</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Price</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Created</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($listings as $listing)
                @php
                $statusColors = [
                    'draft'            => 'bg-gray-100 text-gray-700',
                    'pending_contract' => 'bg-yellow-100 text-yellow-700',
                    'pending_review'   => 'bg-amber-100 text-amber-700',
                    'active'           => 'bg-emerald-100 text-emerald-700',
                    'paused'           => 'bg-blue-100 text-blue-700',
                    'sold'             => 'bg-purple-100 text-purple-700',
                    'expired'          => 'bg-gray-100 text-gray-500',
                    'rejected'         => 'bg-red-100 text-red-700',
                ];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $listing->listing_number }}</td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900">{{ $listing->title_en }}</p>
                        <p class="text-xs text-gray-400">{{ $listing->title_ar }}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $listing->customer?->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $listing->classifiedCategory?->name_en }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $listing->price_formatted }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$listing->status] ?? '' }}">
                            {{ ucfirst(str_replace('_', ' ', $listing->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $listing->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.classifieds.listings.show', $listing) }}"
                           class="text-xs text-primary-600 font-medium hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400">No listings found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $listings->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
