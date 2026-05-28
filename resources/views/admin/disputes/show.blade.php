@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/disputes.js'])
@endpush

@section('title', $dispute->dispute_number . ' — Dispute')

@section('content')

    @php
        $statusBadge = match ($dispute->status) {
            'open' => 'bg-yellow-100 text-yellow-700',
            'seller_responded' => 'bg-blue-100 text-blue-700',
            'under_review' => 'bg-indigo-100 text-indigo-700',
            'escalated' => 'bg-red-100 text-red-700 border border-red-200',
            'resolved' => 'bg-green-100 text-green-700',
            'closed' => 'bg-gray-100 text-gray-500',
            default => 'bg-gray-100 text-gray-500',
        };
        $reasonLabel = ucwords(str_replace('_', ' ', $dispute->reason));
        $statusLabel = ucwords(str_replace('_', ' ', $dispute->status));
        $isClosed = in_array($dispute->status, ['resolved', 'closed'], true);
        $currency = $dispute->order->currency ?? 'USD';
        $compensation = $dispute->compensation_cents
            ? number_format($dispute->compensation_cents / 100, 2)
            : null;
    @endphp

    {{-- ─── Breadcrumb ──────────────────────────────────────────────────────── --}}
    <nav class="mb-5 text-sm text-gray-500 flex items-center gap-1.5">
        <a href="{{ route('admin.disputes.index') }}" class="hover:text-primary-600">Disputes</a>
        <span>/</span>
        <span class="text-gray-800 font-medium font-mono">{{ $dispute->dispute_number }}</span>
    </nav>

    <div class="flex gap-5 items-start">

        {{-- ═════════════════════════════════════════════════════════════════ --}}
        {{-- LEFT: Conversation thread --}}
        {{-- ═════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0 flex flex-col gap-4">

            {{-- Dispute header --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <div class="flex flex-wrap items-start gap-3 mb-2">
                    <span class="font-mono text-sm text-gray-500">{{ $dispute->dispute_number }}</span>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusBadge }} js-status-badge">
                        {{ $statusLabel }}
                    </span>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                        {{ $reasonLabel }}
                    </span>
                </div>
                <h1 class="text-lg font-semibold text-gray-900">Dispute case</h1>
                @if($dispute->description)
                    <p class="mt-2 text-sm text-gray-600 whitespace-pre-wrap">{{ $dispute->description }}</p>
                @endif
            </div>

            {{-- Message thread --}}
            <div id="message-thread" class="space-y-3">
                @foreach($dispute->messages as $msg)
                    @php
                        $role = $msg->sender_role;
                        $isAdmin = $role === 'admin';
                        $isInternal = (bool) $msg->is_internal_note;

                        $bubbleClass = $isInternal
                            ? 'bg-yellow-50 border border-yellow-200'
                            : ($isAdmin
                                ? 'bg-blue-50 border border-blue-200'
                                : ($role === 'seller'
                                    ? 'bg-purple-50 border border-purple-200'
                                    : 'bg-gray-50 border border-gray-200'));

                        $alignClass = $isAdmin ? 'ml-8' : 'mr-8';

                        $authorLabel = match ($role) {
                            'admin' => 'Support Agent',
                            'seller' => $dispute->vendor->store_name ?? 'Vendor',
                            default => $dispute->customer->name ?? 'Customer',
                        };
                    @endphp
                    <div class="message-bubble {{ $alignClass }}" data-message-id="{{ $msg->id }}">
                        <div class="rounded-xl p-4 {{ $bubbleClass }}">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-semibold text-gray-700">{{ $authorLabel }}</span>
                                @if($isInternal)
                                    <span
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-200 text-yellow-800">Internal
                                        note</span>
                                @endif
                                <span class="text-xs text-gray-400 ml-auto">
                                    {{ $msg->created_at ? \Carbon\Carbon::parse($msg->created_at)->format('M d, Y H:i') : '' }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ $msg->message }}</div>
                        </div>
                    </div>
                @endforeach

                @if($dispute->messages->isEmpty())
                    <div class="text-center py-8 text-sm text-gray-400">No messages yet.</div>
                @endif
            </div>

            {{-- Reply form --}}
            @if(!$isClosed)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5" id="reply-area">
                    <form id="reply-form" data-url="{{ route('admin.disputes.reply', $dispute->id) }}" novalidate>
                        @csrf
                        <div id="reply-form-bg" class="mb-3 rounded-lg border border-gray-200 p-0.5 transition-colors">
                            <textarea id="reply-message" name="message" rows="4" placeholder="Write a message to both parties…"
                                class="w-full text-sm p-3 rounded-md resize-none focus:outline-none bg-transparent"
                                required></textarea>
                        </div>

                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <label class="flex items-center gap-2 cursor-pointer select-none text-sm text-gray-600">
                                <input type="checkbox" id="is-internal-note" name="is_internal_note" value="1"
                                    class="rounded border-gray-300 text-yellow-500 focus:ring-yellow-400">
                                Internal note (admins only)
                            </label>
                            <button type="submit" id="btn-reply" class="btn btn-primary btn-sm">
                                Send Reply
                            </button>
                        </div>
                    </form>
                </div>
            @else
                <div class="text-center text-sm text-gray-400 py-4">
                    Dispute is {{ $dispute->status }}. Reopen via status to reply.
                </div>
            @endif

            {{-- Resolve panel --}}
            @if(!$isClosed)
                <x-card>
                    <h3 class="text-sm font-semibold text-gray-800 mb-1">Resolve Dispute</h3>
                    <p class="text-xs text-gray-500 mb-4">Record the final outcome and optional compensation.</p>

                    <form id="resolve-form" data-url="{{ route('admin.disputes.resolve', $dispute->id) }}"
                        class="grid grid-cols-1 md:grid-cols-2 gap-3" novalidate>
                        @csrf

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Outcome</label>
                            <select name="resolution" required class="form-input w-full text-sm">
                                <option value="">Select…</option>
                                <option value="favor_customer">Favor customer</option>
                                <option value="favor_seller">Favor seller</option>
                                <option value="split">Split</option>
                                <option value="no_action">No action</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Compensation ({{ $currency }})
                            </label>
                            <input type="number" name="compensation" step="0.01" min="0" class="form-input w-full text-sm"
                                placeholder="0.00">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Resolution notes</label>
                            <textarea name="resolution_notes" rows="3" class="form-input w-full text-sm"
                                placeholder="Internal record of the decision rationale…"></textarea>
                        </div>

                        <div class="md:col-span-2 flex items-center justify-between gap-3">
                            <label class="flex items-center gap-2 cursor-pointer select-none text-sm text-gray-600">
                                <input type="checkbox" name="close" value="1"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-400">
                                Close thread after resolving
                            </label>
                            <button type="submit" id="btn-resolve" class="btn btn-primary btn-sm">
                                Resolve Dispute
                            </button>
                        </div>
                    </form>
                </x-card>
            @endif

        </div>

        {{-- ═════════════════════════════════════════════════════════════════ --}}
        {{-- RIGHT: Sidebar --}}
        {{-- ═════════════════════════════════════════════════════════════════ --}}
        <div class="w-80 shrink-0 flex flex-col gap-4">

            {{-- Dispute Info --}}
            <x-card>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">Dispute Info</h3>
                <dl class="space-y-3 text-sm">

                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-xs text-gray-500 shrink-0">Status</dt>
                        <dd>
                            <select id="status-select" data-url="{{ route('admin.disputes.update-status', $dispute->id) }}"
                                class="form-input text-xs py-1 pr-7">
                                @foreach(['open', 'seller_responded', 'under_review', 'escalated', 'resolved', 'closed'] as $s)
                                    <option value="{{ $s }}" {{ $dispute->status === $s ? 'selected' : '' }}>
                                        {{ ucwords(str_replace('_', ' ', $s)) }}
                                    </option>
                                @endforeach
                            </select>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500 mb-0.5">Reason</dt>
                        <dd>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                {{ $reasonLabel }}
                            </span>
                        </dd>
                    </div>

                    @if($dispute->resolution)
                        <div>
                            <dt class="text-xs text-gray-500 mb-0.5">Resolution</dt>
                            <dd>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                    {{ ucwords(str_replace('_', ' ', $dispute->resolution)) }}
                                </span>
                            </dd>
                        </div>
                    @endif

                    @if($compensation)
                        <div>
                            <dt class="text-xs text-gray-500 mb-0.5">Compensation</dt>
                            <dd class="text-sm font-medium text-gray-800">
                                {{ $compensation }} {{ $currency }}
                            </dd>
                        </div>
                    @endif

                    @if($dispute->resolution_notes)
                        <div>
                            <dt class="text-xs text-gray-500 mb-0.5">Resolution Notes</dt>
                            <dd class="text-sm text-gray-700 whitespace-pre-wrap">{{ $dispute->resolution_notes }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs text-gray-500 mb-0.5">Customer</dt>
                        <dd>
                            <a href="{{ route('admin.customers.show', $dispute->customer_id) }}"
                                class="text-sm text-primary-600 hover:underline">
                                {{ $dispute->customer->name ?? '—' }}
                            </a>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-gray-500 mb-0.5">Vendor</dt>
                        <dd>
                            <a href="{{ route('admin.vendors.show', $dispute->vendor_id) }}"
                                class="text-sm text-primary-600 hover:underline">
                                {{ $dispute->vendor->store_name ?? '—' }}
                            </a>
                        </dd>
                    </div>

                    @if($dispute->order)
                        <div>
                            <dt class="text-xs text-gray-500 mb-0.5">Order</dt>
                            <dd>
                                <a href="{{ route('admin.orders.show', $dispute->order_id) }}"
                                    class="text-sm text-primary-600 hover:underline font-mono">
                                    {{ $dispute->order->order_number }}
                                </a>
                            </dd>
                        </div>
                    @endif

                    @if($dispute->subOrder)
                        <div>
                            <dt class="text-xs text-gray-500 mb-0.5">Sub-Order</dt>
                            <dd class="text-sm font-mono text-gray-700">
                                {{ $dispute->subOrder->sub_order_number }}
                            </dd>
                        </div>
                    @endif

                    @if($dispute->returnRequest)
                        <div>
                            <dt class="text-xs text-gray-500 mb-0.5">Related Return</dt>
                            <dd class="text-sm font-mono text-gray-700">
                                {{ $dispute->returnRequest->return_number }}
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs text-gray-500 mb-0.5">Opened</dt>
                        <dd class="text-sm text-gray-700">{{ $dispute->created_at->format('M d, Y H:i') }}</dd>
                    </div>

                    @if($dispute->resolved_at)
                        <div>
                            <dt class="text-xs text-gray-500 mb-0.5">Resolved</dt>
                            <dd class="text-sm text-green-700">
                                {{ \Carbon\Carbon::parse($dispute->resolved_at)->format('M d, Y H:i') }}
                            </dd>
                        </div>
                    @endif

                </dl>
            </x-card>

            {{-- Assigned to --}}
            <x-card>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Assigned To</h3>

                <p id="assignee-name" class="text-sm font-medium text-gray-800 mb-3">
                    {{ $dispute->assignedToAdmin?->name ?? 'Unassigned' }}
                </p>

                <div class="flex flex-col gap-2">
                    <button id="btn-assign-me" data-url="{{ route('admin.disputes.assign-me', $dispute->id) }}"
                        data-my-name="{{ $admin->name }}" class="btn btn-secondary btn-sm w-full">
                        Assign to me
                    </button>

                    <div class="flex gap-2">
                        <select id="reassign-select" class="form-input flex-1 text-xs py-1">
                            <option value="">Reassign to…</option>
                            @foreach($admins as $a)
                                <option value="{{ $a->id }}" {{ $dispute->assigned_to_admin_id === $a->id ? 'selected' : '' }}>
                                    {{ $a->name }}
                                </option>
                            @endforeach
                        </select>
                        <button id="btn-reassign" data-url="{{ route('admin.disputes.assign', $dispute->id) }}"
                            class="btn btn-primary btn-sm shrink-0">
                            Save
                        </button>
                    </div>
                </div>
            </x-card>

        </div>

    </div>

@endsection