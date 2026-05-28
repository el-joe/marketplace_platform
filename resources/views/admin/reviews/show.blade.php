@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/reviews.js'])
@endpush

@section('title', 'Review: ' . ($review->product?->name_en ?? 'Review'))

@section('content')

    {{-- ─── Header ───────────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.reviews.index') }}" class="hover:text-primary-600">Reviews</a>
                <span>/</span>
                <span class="text-gray-800">{{ $review->product?->name_en ?? 'Review' }}</span>
            </div>
            <h1 class="text-xl font-bold text-gray-900">Review Detail</h1>
        </div>
        <a href="{{ route('admin.reviews.index') }}" class="btn btn-secondary btn-sm">← Back to Reviews</a>
    </div>

    <div class="grid grid-cols-12 gap-6">

        {{-- ═══════ LEFT: Review Content (8 cols) ═══════ --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">

            {{-- ─── Review Detail Card ────────────────────────────────────────────── --}}
            <x-card>
                <div class="space-y-4">

                    {{-- Product --}}
                    <div class="flex items-center gap-3">
                        @php $img = $review->product?->primaryImage->first(); @endphp
                        @if($img)
                            <img src="{{ $img->url }}" class="w-12 h-12 object-cover rounded border border-gray-100" alt="">
                        @endif
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Product</p>
                            @if($review->product)
                                <a href="{{ route('admin.products.show', $review->product->id) }}"
                                    class="font-semibold text-primary-600 hover:underline" target="_blank">
                                    {{ $review->product->name_en }}
                                </a>
                            @else
                                <span class="text-gray-500">—</span>
                            @endif
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Customer + metadata --}}
                    <div class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Customer</p>
                            @if($review->customer)
                                <a href="{{ route('admin.customers.show', $review->customer->id) }}"
                                    class="text-primary-600 hover:underline">
                                    {{-- Mask only last characters --}}
                                    @php
                                        $parts = explode(' ', $review->customer->name);
                                        $masked = implode(' ', array_map(function ($p) {
                                            $len = mb_strlen($p);
                                            if ($len <= 2)
                                                return $p;
                                            $v = max(1, (int) ceil($len / 3));
                                            return mb_substr($p, 0, $v) . str_repeat('*', $len - $v);
                                        }, $parts));
                                    @endphp
                                    {{ $masked }}
                                </a>
                            @else
                                <span class="text-gray-500">Guest</span>
                            @endif
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Country</p>
                            <span>{{ $review->country?->flag_emoji ?? '' }} {{ $review->country?->name_en ?? '—' }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Submitted</p>
                            <span>{{ $review->created_at->format('d M Y H:i') }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Moderated by</p>
                            <span>{{ $review->moderatedByAdmin?->name ?? '—' }}</span>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Rating --}}
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-1">Rating</p>
                        <div class="flex items-center gap-2">
                            <span class="text-2xl leading-none">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
                                @endfor
                            </span>
                            <span class="text-lg font-bold text-gray-700">{{ $review->rating }}/5</span>
                        </div>
                    </div>

                    {{-- Title + Body --}}
                    @if($review->title)
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-1">Title</p>
                            <p class="font-semibold text-gray-800">{{ $review->title }}</p>
                        </div>
                    @endif

                    @if($review->body)
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-1">Review</p>
                            <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{{ $review->body }}</p>
                        </div>
                    @endif

                    {{-- Review images --}}
                    @if($review->files->isNotEmpty())
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-2">Attached Images</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($review->files as $file)
                                    <a href="{{ Storage::url($file->path) }}" target="_blank">
                                        <img src="{{ Storage::url($file->path) }}"
                                            class="w-16 h-16 object-cover rounded border border-gray-200 hover:opacity-80" alt="">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <hr class="border-gray-100">

                    {{-- Badges row --}}
                    <div class="flex flex-wrap items-center gap-3 text-sm">
                        {{-- Status --}}
                        @php
                            $statusColors = ['pending' => 'warning', 'published' => 'success', 'rejected' => 'danger', 'flagged' => 'orange', 'auto_flagged' => 'orange'];
                            $sc = $statusColors[$review->status] ?? 'gray';
                        @endphp
                        <div>
                            <span class="text-xs text-gray-400 uppercase font-medium mr-1">Status</span>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-100 text-{{ $sc }}-700">
                                {{ ucwords(str_replace('_', ' ', $review->status)) }}
                            </span>
                        </div>

                        {{-- Verified purchase --}}
                        @if($review->is_verified_purchase)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                ✓ Verified Purchase
                            </span>
                        @endif

                        {{-- Helpful votes --}}
                        <span class="text-gray-500">
                            👍 {{ $review->helpful_count }}
                            <span class="mx-1">·</span>
                            👎 {{ $review->not_helpful_count }}
                        </span>
                    </div>

                    {{-- AI Flag reason --}}
                    @if($review->ai_flag_reason)
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-yellow-600 mt-0.5 flex-shrink-0" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <p class="text-xs font-semibold text-yellow-800 mb-0.5">AI Flag Reason</p>
                                    <p class="text-sm text-yellow-700">{{ $review->ai_flag_reason }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </x-card>

            {{-- ─── Vendor Reply ───────────────────────────────────────────────────── --}}
            @if($review->vendorReply)
                @php $reply = $review->vendorReply; @endphp
                <x-card title="Vendor Reply">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div>
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">From</p>
                            <p class="font-medium text-gray-800">{{ $reply->vendor?->store_name ?? '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">Status</p>
                            @php $rc = $reply->status === 'published' ? 'success' : 'gray'; @endphp
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $rc }}-100 text-{{ $rc }}-700">
                                {{ ucfirst($reply->status) }}
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap mb-4">{{ $reply->body }}</p>
                    <div class="flex gap-2">
                        @if($reply->status !== 'hidden')
                            <button type="button" class="btn btn-secondary btn-sm js-reply-toggle-btn" data-action="hide"
                                data-url="{{ route('admin.reviews.vendor-replies.hide', $reply->id) }}">
                                Hide Reply
                            </button>
                        @endif
                        @if($reply->status !== 'published')
                            <button type="button" class="btn btn-success btn-sm js-reply-toggle-btn" data-action="show"
                                data-url="{{ route('admin.reviews.vendor-replies.show', $reply->id) }}">
                                Show Reply
                            </button>
                        @endif
                    </div>
                </x-card>
            @endif

        </div>{{-- end left column --}}

        {{-- ═══════ RIGHT: Moderation Panel (4 cols) ═══════ --}}
        <div class="col-span-12 lg:col-span-4 space-y-5">

            {{-- ─── Moderation Actions ─────────────────────────────────────────────── --}}
            <x-card title="Moderation">
                @if(in_array($review->status, ['pending', 'flagged', 'auto_flagged']))
                    <div class="space-y-2">
                        <button type="button" class="w-full btn btn-primary" id="approve-btn"
                            data-url="{{ route('admin.reviews.approve', $review->id) }}">
                            Approve &amp; Publish
                        </button>
                        <div>
                            <button type="button" class="w-full btn btn-danger" id="open-reject-modal-btn">
                                Reject Review
                            </button>
                        </div>
                    </div>
                @elseif($review->status === 'published')
                    <div class="rounded-lg bg-green-50 border border-green-100 px-4 py-3 text-sm text-green-800 mb-3">
                        ✓ Published
                    </div>
                    <button type="button" class="w-full btn btn-danger" id="open-reject-modal-btn">
                        Un-publish &amp; Reject
                    </button>
                @elseif($review->status === 'rejected')
                    <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-800 mb-3">
                        ✗ Rejected
                    </div>
                    <button type="button" class="w-full btn btn-primary" id="approve-btn"
                        data-url="{{ route('admin.reviews.approve', $review->id) }}">
                        Re-approve
                    </button>
                @endif

                {{-- Delete --}}
                @can('delete', $review)
                    <button type="button" class="w-full btn btn-ghost btn-sm mt-3 text-red-600 hover:bg-red-50" id="delete-btn"
                        data-url="{{ route('admin.reviews.delete', $review->id) }}"
                        data-redirect="{{ route('admin.reviews.index') }}">
                        Delete permanently
                    </button>
                @endcan
            </x-card>

            {{-- ─── Quick Info ───────────────────────────────────────────────────────── --}}
            <x-card title="Quick Info">
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Review ID</dt>
                        <dd class="font-mono text-xs text-gray-600 truncate max-w-[160px]" title="{{ $review->id }}">
                            {{ substr($review->id, 0, 8) }}…</dd>
                    </div>
                    @if($review->orderItem)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Order</dt>
                            <dd>
                                <a href="{{ route('admin.orders.show', $review->orderItem->order_id) }}"
                                    class="text-primary-600 hover:underline text-xs">
                                    View order
                                </a>
                            </dd>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Verified purchase</dt>
                        <dd>{{ $review->is_verified_purchase ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Helpful votes</dt>
                        <dd>👍 {{ $review->helpful_count }} / 👎 {{ $review->not_helpful_count }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Submitted</dt>
                        <dd class="text-xs">{{ $review->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    @if($review->moderatedByAdmin)
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Moderated by</dt>
                            <dd class="text-xs">{{ $review->moderatedByAdmin->name }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

        </div>{{-- end right column --}}
    </div>

    {{-- ─── Reject Modal ─────────────────────────────────────────────────────────── --}}
    <x-modal id="reject-modal" title="Reject Review" size="sm">
        <p class="text-sm text-gray-600 mb-3">This review will be rejected and soft-deleted. Add an optional internal note:
        </p>
        <textarea id="reject-reason-input" rows="3" class="form-input w-full text-sm"
            placeholder="Optional reason…"></textarea>
        <div class="flex justify-end gap-3 mt-4">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-btn" class="btn btn-danger"
                data-url="{{ route('admin.reviews.reject', $review->id) }}"
                data-redirect="{{ route('admin.reviews.index') }}">
                Confirm Reject
            </button>
        </div>
    </x-modal>

    <script>
        window.reviewShow = {
            approveUrl: @json(route('admin.reviews.approve', $review->id)),
            rejectUrl: @json(route('admin.reviews.reject', $review->id)),
            deleteUrl: @json(route('admin.reviews.delete', $review->id)),
            indexUrl: @json(route('admin.reviews.index')),
        };
    </script>

@endsection