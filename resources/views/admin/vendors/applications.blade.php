@extends('layouts.admin')

@section('title', 'Application Queue')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Application Queue</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $vendors->total() }} pending vendor application(s) — oldest first.
            </p>
        </div>
        <a href="{{ route('admin.vendors.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← All Vendors</a>
    </div>

    @if($vendors->isEmpty())
        <x-empty-state title="No pending applications" description="All vendor applications have been reviewed." icon="inbox" />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($vendors as $vendor)
                @php
                    $daysWaiting = $vendor->created_at->diffInDays(now());
                    $waitColor = $daysWaiting < 3 ? 'gray' : ($daysWaiting < 7 ? 'warning' : 'danger');
                    $docsTotal = $vendor->documents->count();
                    $docsVerified = $vendor->documents->filter(fn($d) => in_array($d->status, ['approved', 'verified']))->count();
                    $docProgress = $docsTotal > 0 ? round(($docsVerified / $docsTotal) * 100) : 0;
                @endphp

                <div
                    class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-4 hover:border-primary-300 transition-colors">
                    {{-- Header --}}
                    <div class="flex items-start gap-3">
                        @if($vendor->avatar)
                            <img src="{{ $vendor->avatar }}"
                                class="w-12 h-12 rounded-lg object-cover flex-shrink-0 border border-gray-200">
                        @else
                            <div
                                class="w-12 h-12 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center text-lg font-bold flex-shrink-0">
                                {{ strtoupper(substr($vendor->store_name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-gray-900 truncate">{{ $vendor->store_name }}</h3>
                            <p class="text-xs text-gray-500 truncate">{{ $vendor->business_name ?: $vendor->name }}</p>
                        </div>
                        <x-badge :color="$waitColor" class="flex-shrink-0 text-xs">
                            {{ $daysWaiting }}d
                        </x-badge>
                    </div>

                    {{-- Info grid --}}
                    <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs">
                        <div>
                            <span class="text-gray-400">Email</span>
                            <div class="text-gray-800 truncate">{{ $vendor->email }}</div>
                        </div>
                        <div>
                            <span class="text-gray-400">Country</span>
                            <div class="text-gray-800">{{ $vendor->country?->name_en ?? '—' }}</div>
                        </div>
                        <div>
                            <span class="text-gray-400">Applied</span>
                            <div class="text-gray-800">{{ $vendor->created_at->format('d M Y') }}</div>
                        </div>
                        <div>
                            <span class="text-gray-400">Business type</span>
                            <div class="text-gray-800">{{ ucfirst(str_replace('_', ' ', $vendor->business_type ?? '—')) }}</div>
                        </div>
                    </div>

                    {{-- Document progress --}}
                    <div>
                        <div class="flex justify-between items-center mb-1 text-xs">
                            <span class="text-gray-500">Documents verified</span>
                            <span
                                class="font-medium {{ $docsVerified === $docsTotal && $docsTotal > 0 ? 'text-success-700' : 'text-gray-700' }}">
                                {{ $docsVerified }} / {{ $docsTotal }}
                            </span>
                        </div>
                        <div class="h-1.5 rounded-full bg-gray-200 overflow-hidden">
                            <div class="h-full rounded-full transition-all
                                        {{ $docProgress === 100 ? 'bg-success-500' : ($docProgress >= 50 ? 'bg-warning-500' : 'bg-danger-500') }}"
                                style="width: {{ $docProgress }}%"></div>
                        </div>
                    </div>

                    {{-- Waiting badge --}}
                    <div
                        class="flex items-center gap-1 text-xs
                                {{ $waitColor === 'danger' ? 'text-danger-600' : ($waitColor === 'warning' ? 'text-warning-700' : 'text-gray-500') }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Waiting {{ $daysWaiting }} day(s)
                        @if($daysWaiting >= 7)
                            — <strong>Action required</strong>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2 pt-1 border-t border-gray-100">
                        <a href="{{ route('admin.vendors.show', $vendor->id) }}"
                            class="flex-1 btn btn-primary btn-sm text-center text-xs" data-review-vendor="{{ $vendor->id }}">
                            Review
                        </a>
                        <button type="button" class="flex-1 btn btn-success btn-sm text-xs" data-approve-vendor="{{ $vendor->id }}">
                            Approve
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm text-danger-600 text-xs"
                            data-open-modal="quick-reject-modal" data-vendor-id="{{ $vendor->id }}"
                            data-vendor-name="{{ $vendor->store_name }}">
                            Reject
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $vendors->links() }}
        </div>
    @endif

    {{-- Quick-reject modal --}}
    <x-modal id="quick-reject-modal" title="Reject Application" size="sm">
        <form id="quick-reject-form">
            @csrf
            <input type="hidden" id="qr-vendor-id">
            <p class="text-sm text-gray-600 mb-3">Rejecting <strong id="qr-vendor-name"></strong>.</p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span
                        class="text-danger-500">*</span></label>
                <textarea name="reason" class="form-input w-full resize-none" rows="3"
                    placeholder="Explain why this application is being rejected…" required></textarea>
            </div>
            <x-slot name="footer">
                <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
            </x-slot>
        </form>
    </x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/vendors.js'])
@endpush