@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/ad-campaigns.js'])
@endpush

@section('title', 'Booking: ' . $paidAdBooking->booking_reference)

@section('content')

    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.paid-ad-bookings.index') }}" class="hover:text-primary-600">Paid Bookings</a>
                <span>/</span>
                <span class="font-mono text-gray-800">{{ $paidAdBooking->booking_reference }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 font-mono">{{ $paidAdBooking->booking_reference }}</h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @if($paidAdBooking->status === 'pending')
                <button type="button"
                    class="btn btn-success js-approve-booking-btn"
                    data-url="{{ route('admin.paid-ad-bookings.approve', $paidAdBooking->id) }}"
                    data-ref="{{ e($paidAdBooking->booking_reference) }}">
                    Approve Booking
                </button>
                <button type="button"
                    class="btn btn-danger js-reject-booking-btn"
                    data-url="{{ route('admin.paid-ad-bookings.reject', $paidAdBooking->id) }}"
                    data-ref="{{ e($paidAdBooking->booking_reference) }}">
                    Reject Booking
                </button>
            @endif
            <a href="{{ route('admin.paid-ad-bookings.index') }}" class="btn btn-secondary">← Back</a>
        </div>
    </div>

    @if($paidAdBooking->status === 'rejected' && $paidAdBooking->rejection_reason)
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <strong>Rejection reason:</strong> {{ $paidAdBooking->rejection_reason }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        {{-- ─── Booking Info ─────────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">
            <x-card title="Booking Details">
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-4 gap-y-3 text-sm">
                    @php
                        $statusColors = ['pending'=>'warning','active'=>'success','rejected'=>'danger','cancelled'=>'gray','ended'=>'gray'];
                        $sc = $statusColors[$paidAdBooking->status] ?? 'gray';
                        $payColors = ['unpaid'=>'danger','paid'=>'success','invoiced'=>'warning','refunded'=>'gray'];
                        $pc = $payColors[$paidAdBooking->payment_status ?? 'unpaid'] ?? 'gray';
                    @endphp
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-100 text-{{ $sc }}-700">
                                {{ ucfirst($paidAdBooking->status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Payment</dt>
                        <dd>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $pc }}-100 text-{{ $pc }}-700">
                                {{ ucfirst($paidAdBooking->payment_status ?? 'unpaid') }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Agreed Rate</dt>
                        <dd class="font-semibold">${{ number_format($paidAdBooking->agreed_rate_cents / 100, 2) }} <span class="text-xs text-gray-400">{{ strtoupper($paidAdBooking->currency ?? 'USD') }}</span></dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Total Charged</dt>
                        <dd class="font-semibold">
                            {{ $paidAdBooking->total_charged ? '$' . number_format($paidAdBooking->total_charged, 2) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Booked From</dt>
                        <dd>{{ $paidAdBooking->booked_from ? \Carbon\Carbon::parse($paidAdBooking->booked_from)->format('d M Y') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Booked Until</dt>
                        <dd>{{ $paidAdBooking->booked_until ? \Carbon\Carbon::parse($paidAdBooking->booked_until)->format('d M Y') : '—' }}</dd>
                    </div>
                    @if($paidAdBooking->approvedByAdmin)
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Approved By</dt>
                            <dd>{{ $paidAdBooking->approvedByAdmin->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Approved At</dt>
                            <dd>{{ $paidAdBooking->approved_at ? \Carbon\Carbon::parse($paidAdBooking->approved_at)->format('d M Y H:i') : '—' }}</dd>
                        </div>
                    @endif
                    @if($paidAdBooking->vendor_notes)
                        <div class="col-span-full">
                            <dt class="text-gray-500 text-xs uppercase font-medium mb-0.5">Vendor Notes</dt>
                            <dd class="text-gray-700">{{ $paidAdBooking->vendor_notes }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            {{-- ─── Creatives ──────────────────────────────────────────────────────── --}}
            <x-card title="Ad Creatives">
                @if($paidAdBooking->creatives->isEmpty())
                    <p class="text-sm text-gray-400 py-6 text-center">No creatives uploaded yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($paidAdBooking->creatives as $creative)
                            @php
                                $crColors = ['pending_review'=>'warning','approved'=>'success','rejected'=>'danger','draft'=>'gray'];
                                $cc = $crColors[$creative->status] ?? 'gray';
                            @endphp
                            <div class="border border-gray-100 rounded-lg p-4">
                                <div class="flex items-start justify-between gap-4 flex-wrap">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $cc }}-100 text-{{ $cc }}-700">
                                                {{ ucwords(str_replace('_', ' ', $creative->status)) }}
                                            </span>
                                            @if($creative->is_current)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-700">Current</span>
                                            @endif
                                        </div>
                                        <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                            <div>
                                                <dt class="text-xs text-gray-400">Title</dt>
                                                <dd class="font-medium">{{ $creative->title ?? '—' }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-xs text-gray-400">Type</dt>
                                                <dd>{{ ucfirst($creative->creative_type ?? '—') }}</dd>
                                            </div>
                                            @if($creative->headline)
                                                <div class="col-span-2">
                                                    <dt class="text-xs text-gray-400">Headline</dt>
                                                    <dd>{{ $creative->headline }}</dd>
                                                </div>
                                            @endif
                                            @if($creative->description)
                                                <div class="col-span-2">
                                                    <dt class="text-xs text-gray-400">Description</dt>
                                                    <dd class="text-gray-600">{{ $creative->description }}</dd>
                                                </div>
                                            @endif
                                            @if($creative->cta_text)
                                                <div>
                                                    <dt class="text-xs text-gray-400">CTA Text</dt>
                                                    <dd>{{ $creative->cta_text }}</dd>
                                                </div>
                                            @endif
                                            @if($creative->destination_url)
                                                <div class="col-span-2">
                                                    <dt class="text-xs text-gray-400">Destination URL</dt>
                                                    <dd class="break-all text-primary-600 text-xs">{{ $creative->destination_url }}</dd>
                                                </div>
                                            @endif
                                            @if($creative->rejection_reason)
                                                <div class="col-span-2">
                                                    <dt class="text-xs text-red-400">Rejection Reason</dt>
                                                    <dd class="text-red-700 text-sm">{{ $creative->rejection_reason }}</dd>
                                                </div>
                                            @endif
                                            @if($creative->reviewedByAdmin)
                                                <div class="col-span-2 text-xs text-gray-400 mt-1">
                                                    Reviewed by {{ $creative->reviewedByAdmin->name }} at {{ \Carbon\Carbon::parse($creative->reviewed_at)->format('d M Y H:i') }}
                                                </div>
                                            @endif
                                        </dl>
                                    </div>

                                    {{-- Review actions --}}
                                    @if($creative->status === 'pending_review')
                                        <div class="flex gap-2 flex-shrink-0">
                                            <button type="button"
                                                class="btn btn-success btn-sm js-approve-creative-btn"
                                                data-url="{{ route('admin.paid-ad-bookings.creatives.review', $creative->id) }}"
                                                data-id="{{ $creative->id }}">
                                                Approve
                                            </button>
                                            <button type="button"
                                                class="btn btn-danger btn-sm js-reject-creative-btn"
                                                data-url="{{ route('admin.paid-ad-bookings.creatives.review', $creative->id) }}"
                                                data-id="{{ $creative->id }}">
                                                Reject
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        {{-- ─── Sidebar: Slot & Vendor ───────────────────────────────────────────── --}}
        <div class="space-y-5">
            <x-card title="Ad Slot">
                @if($paidAdBooking->slot)
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-gray-400">Slot Name</dt>
                            <dd class="font-medium">{{ $paidAdBooking->slot->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">Slot Code</dt>
                            <dd class="font-mono text-xs">{{ $paidAdBooking->slot->slot_code }}</dd>
                        </div>
                        @if($paidAdBooking->slot->placementDefinition)
                            <div>
                                <dt class="text-xs text-gray-400">Placement</dt>
                                <dd>{{ $paidAdBooking->slot->placementDefinition->name }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs text-gray-400">Pricing Model</dt>
                            <dd class="uppercase">{{ $paidAdBooking->slot->pricing_model ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">Base Rate</dt>
                            <dd>${{ number_format(($paidAdBooking->slot->base_rate_cents ?? 0) / 100, 2) }}</dd>
                        </div>
                        <div class="pt-2">
                            <a href="{{ route('admin.ad-slots.index') }}" class="text-xs text-primary-600 hover:underline">View all slots →</a>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-gray-400">Slot not found.</p>
                @endif
            </x-card>

            <x-card title="Vendor">
                @if($paidAdBooking->vendor)
                    <dl class="space-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-gray-400">Store Name</dt>
                            <dd class="font-medium">{{ $paidAdBooking->vendor->store_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400">Country</dt>
                            <dd>{{ $paidAdBooking->country?->name_en ?? '—' }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="text-sm text-gray-400">Vendor not found.</p>
                @endif
            </x-card>
        </div>
    </div>

    {{-- ─── Modals ──────────────────────────────────────────────────────────────── --}}
    <x-modal id="approve-booking-modal" title="Approve Booking" size="sm">
        <p class="text-sm text-gray-600">Approve booking <strong id="approve-booking-ref" class="font-mono"></strong>? It will become active.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#approve-booking-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-approve-booking-btn" class="btn btn-success">Approve</button>
        </div>
    </x-modal>

    <x-modal id="reject-booking-modal" title="Reject Booking" size="md">
        <p class="text-sm text-gray-600 mb-3">Reject booking <strong id="reject-booking-ref" class="font-mono"></strong>.</p>
        <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
        <textarea id="reject-booking-reason" rows="3" class="form-input w-full text-sm" placeholder="Explain why this booking is being rejected…"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="reject-booking-reason-error">Reason is required.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-booking-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-booking-btn" class="btn btn-danger">Reject</button>
        </div>
    </x-modal>

    <x-modal id="reject-creative-modal" title="Reject Creative" size="md">
        <p class="text-sm text-gray-600 mb-3">Provide a reason for rejecting this creative.</p>
        <div class="mb-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Code</label>
            <select id="creative-rejection-code" class="form-input w-full text-sm">
                <option value="">Select code (optional)</option>
                <option value="prohibited_content">Prohibited Content</option>
                <option value="misleading">Misleading / False Claims</option>
                <option value="low_quality">Low Quality / Pixelated</option>
                <option value="wrong_dimensions">Wrong Dimensions</option>
                <option value="trademark">Trademark / Copyright Issue</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
            <textarea id="creative-rejection-reason" rows="3" class="form-input w-full text-sm" placeholder="Explain why this creative is being rejected…"></textarea>
            <p class="text-xs text-red-500 hidden mt-1" id="creative-rejection-reason-error">Reason is required.</p>
        </div>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-creative-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-creative-btn" class="btn btn-danger">Reject Creative</button>
        </div>
    </x-modal>

@endsection
