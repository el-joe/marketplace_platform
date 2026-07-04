@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', __('admin.secret_promotions.secret_promotion_detail'))

@section('content')

    {{-- ─── Breadcrumbs ─────────────────────────────────────────────────────────── --}}
    <nav class="flex text-sm text-gray-500 mb-4">
        <a href="{{ route('admin.secret-promotions.index') }}" class="hover:text-gray-700">{{ __('admin.secret_promotions.title') }}</a>
        <span class="mx-2">/</span>
        <span
            class="text-gray-900 font-medium truncate">{{ $promotion->vendorListing?->product?->name_en ?? __('admin.secret_promotions.promotion_hash', ['id' => $promotion->id]) }}</span>
    </nav>

    {{-- ─── Page header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                @if($promotion->vendorListing?->product?->images?->first())
                    <img src="{{ $promotion->vendorListing->product->images->first()->url }}" alt=""
                        class="w-full h-full object-cover">
                @else
                    <svg class="w-7 h-7 m-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                @endif
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $promotion->vendorListing?->product?->name_en ?? '—' }}</h1>
                <p class="text-sm text-gray-500">
                    {{ $promotion->vendor?->store_name ?? '—' }}
                    &nbsp;·&nbsp;
                    @if($promotion->marketer)
                        <span class="text-green-700">{{ $promotion->marketer->name }}</span>
                    @else
                        <span class="text-gray-400 italic">{{ __('admin.secret_promotions.open_to_all_eligible_marketers') }}</span>
                    @endif
                    &nbsp;·&nbsp;
                    {{ __('admin.secret_promotions.created') }} {{ $promotion->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span id="status-badge"
                class="badge badge-{{ $promotion->status_color }} text-sm px-3 py-1">{{ ucfirst($promotion->status) }}</span>
            <button type="button" id="edit-promo-btn" class="btn btn-outline btn-sm">{{ __('admin.secret_promotions.edit') }}</button>
            @if($promotion->status === 'active')
                <button type="button" id="toggle-status-btn" class="btn btn-warning btn-sm" data-action="pause">{{ __('admin.secret_promotions.pause') }}</button>
            @elseif($promotion->status === 'paused')
                <button type="button" id="toggle-status-btn" class="btn btn-success btn-sm" data-action="resume">{{ __('admin.secret_promotions.resume') }}</button>
            @endif
            @if($promotion->status !== 'expired')
                <button type="button" id="expire-btn" class="btn btn-danger btn-sm">{{ __('admin.secret_promotions.force_expire') }}</button>
            @endif
            <button type="button" id="duplicate-btn" class="btn btn-ghost btn-sm">{{ __('admin.secret_promotions.duplicate') }}</button>
        </div>
    </div>

    {{-- ─── Admin-only banner ───────────────────────────────────────────────────── --}}
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-start gap-3 mb-6">
        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <p class="text-sm text-amber-800">{{ __('admin.secret_promotions.admin_only_view_banner') }}</p>
    </div>

    <div class="grid grid-cols-3 gap-6">

        {{-- ─── MAIN COLUMN ─────────────────────────────────────────────────────── --}}
        <div class="col-span-2 space-y-6">

            {{-- ── Commission split card ──────────────────────────────────────── --}}
            <x-card>
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">{{ __('admin.secret_promotions.commission_split') }} <span
                        class="text-amber-600 normal-case font-normal text-xs">🔒 {{ __('admin.secret_promotions.admin_only') }}</span></h2>
                <div class="grid grid-cols-3 gap-4">
                    {{-- Total --}}
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                        <p class="text-xs font-medium text-blue-500 uppercase">{{ __('admin.secret_promotions.total_commission') }}</p>
                        <p class="text-3xl font-bold text-blue-700 mt-1">{{ $promotion->total_commission_pct }}<span
                                class="text-lg font-medium">%</span></p>
                        <p class="text-xs text-blue-600 mt-1">{{ __('admin.secret_promotions.vendor_pays_this_every_sale') }}</p>
                        <p class="text-sm font-bold text-blue-800 mt-2">= {{ $promotion->listing_price_formatted }}</p>
                    </div>
                    {{-- Marketer --}}
                    <div class="bg-green-50 border border-green-100 rounded-xl p-4">
                        <p class="text-xs font-medium text-green-500 uppercase">{{ __('admin.secret_promotions.marketer_earns') }}</p>
                        <p class="text-3xl font-bold text-green-700 mt-1">{{ $promotion->marketer_share_pct }}<span
                                class="text-lg font-medium">%</span></p>
                        <p class="text-xs text-green-600 mt-1">{{ __('admin.secret_promotions.marketer_sees_only_this') }}</p>
                        <p class="text-sm font-bold text-green-800 mt-2">= {{ $promotion->marketer_earnings_format }}</p>
                    </div>
                    {{-- Admin --}}
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <p class="text-xs font-medium text-amber-500 uppercase">🔒 {{ __('admin.secret_promotions.admin_earns') }}</p>
                        <p class="text-3xl font-bold text-amber-700 mt-1">{{ $promotion->admin_share_pct }}<span
                                class="text-lg font-medium">%</span></p>
                        <p class="text-xs text-amber-600 mt-1">{{ __('admin.secret_promotions.silent_platform_cut') }}</p>
                        <p class="text-sm font-bold text-amber-800 mt-2">= {{ $promotion->admin_earnings_format }}</p>
                    </div>
                </div>

                {{-- Visual bar --}}
                <div class="mt-4">
                    <div class="flex h-6 rounded-full overflow-hidden">
                        @php
                            $totalPct = $promotion->total_commission_pct;
                            $marketerPct = $totalPct > 0 ? round($promotion->marketer_share_pct / $totalPct * 100, 1) : 0;
                            $adminPct = $totalPct > 0 ? round($promotion->admin_share_pct / $totalPct * 100, 1) : 0;
                            $remainPct = max(0, 100 - $marketerPct - $adminPct);
                        @endphp
                        <div class="bg-green-500 flex items-center justify-center text-white text-xs font-bold"
                            style="width: {{ $marketerPct }}%">
                            @if($marketerPct > 8) {{ $promotion->marketer_share_pct }}% @endif
                        </div>
                        <div class="bg-amber-500 flex items-center justify-center text-white text-xs font-bold"
                            style="width: {{ $adminPct }}%">
                            @if($adminPct > 8) {{ $promotion->admin_share_pct }}% @endif
                        </div>
                        <div class="bg-gray-200 flex-1 flex items-center justify-center text-gray-500 text-xs">
                            {{ __('admin.secret_promotions.vendor_margin') }}
                        </div>
                    </div>
                    <div class="flex text-xs mt-2 gap-5 text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-green-500 rounded-full"></span>
                            {{ __('admin.secret_promotions.marketer') }} {{ $promotion->marketer_share_pct }}%</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-amber-500 rounded-full"></span> 🔒
                            {{ __('admin.secret_promotions.admin_pct') }} {{ $promotion->admin_share_pct }}%</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-gray-200 rounded-full"></span>
                            {{ __('admin.secret_promotions.vendor_margin') }}</span>
                    </div>
                </div>
            </x-card>

            {{-- ── Product economics ───────────────────────────────────────────── --}}
            <x-card>
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">🔒 {{ __('admin.secret_promotions.product_economics') }} <span
                        class="text-amber-600 normal-case font-normal text-xs">({{ __('admin.secret_promotions.admin_only') }})</span></h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <p class="text-xs text-gray-400 uppercase">{{ __('admin.secret_promotions.listing_price') }}</p>
                        <p class="text-lg font-bold text-gray-900 mt-1">{{ $promotion->listing_price_formatted }}</p>
                    </div>
                    <div class="text-center p-3 bg-amber-50 rounded-xl border border-amber-100">
                        <p class="text-xs text-amber-500 uppercase">🔒 {{ __('admin.secret_promotions.product_value') }}</p>
                        <p class="text-lg font-bold text-amber-800 mt-1">{{ $promotion->product_value_formatted }}</p>
                    </div>
                    <div class="text-center p-3 bg-primary-50 rounded-xl">
                        <p class="text-xs text-primary-500 uppercase">🔒 {{ __('admin.secret_promotions.margin') }}</p>
                        <p class="text-lg font-bold text-primary-700 mt-1">{{ number_format($promotion->margin_pct, 1) }}%
                        </p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-xl">
                        <p class="text-xs text-gray-400 uppercase">{{ __('admin.secret_promotions.conversions') }}</p>
                        <p class="text-lg font-bold text-gray-900 mt-1">{{ $promotion->conversion_count }}</p>
                    </div>
                </div>

                {{-- Lifetime totals --}}
                <div class="mt-4 border-t border-gray-100 pt-4 grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-400">{{ __('admin.secret_promotions.lifetime_revenue_generated') }}</p>
                        <p class="text-lg font-bold text-primary-700 mt-0.5">
                            {{ number_format($promotion->total_revenue_generated / 100, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-amber-500">🔒 {{ __('admin.secret_promotions.lifetime_admin_earned') }}</p>
                        <p class="text-lg font-bold text-amber-700 mt-0.5">
                            {{ number_format($promotion->total_admin_earned / 100, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-green-500">{{ __('admin.secret_promotions.lifetime_marketer_earned') }}</p>
                        <p class="text-lg font-bold text-green-700 mt-0.5">
                            {{ number_format($promotion->conversions->sum('commission_amount_cents') / 100, 2) }}
                        </p>
                    </div>
                </div>
            </x-card>

            {{-- ── Conversions Chart ───────────────────────────────────────────── --}}
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">{{ __('admin.secret_promotions.conversion_history') }}</h2>
                    <select id="chart-range" class="form-input text-sm py-1 w-36">
                        <option value="30">{{ __('admin.secret_promotions.last_30_days') }}</option>
                        <option value="90">{{ __('admin.secret_promotions.last_90_days') }}</option>
                        <option value="180">{{ __('admin.secret_promotions.last_180_days') }}</option>
                    </select>
                </div>
                <canvas id="conversions-chart" height="200"></canvas>
            </x-card>

            {{-- ── Conversions table ───────────────────────────────────────────── --}}
            <x-card>
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">{{ __('admin.secret_promotions.conversions_log') }}</h2>
                <table id="conversions-table" class="w-full text-sm" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('admin.secret_promotions.date') }}</th>
                            <th>{{ __('admin.secret_promotions.order') }}</th>
                            <th>{{ __('admin.secret_promotions.customer') }}</th>
                            <th>{{ __('admin.secret_promotions.marketer') }}</th>
                            <th>{{ __('admin.secret_promotions.sale_price') }}</th>
                            <th class="bg-green-50 text-green-700">{{ __('admin.secret_promotions.marketer_earned') }}</th>
                            <th class="bg-amber-50 text-amber-700">🔒 {{ __('admin.secret_promotions.admin_earned') }}</th>
                            <th>{{ __('admin.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conversions as $c)
                            <tr>
                                <td class="text-xs text-gray-500">{{ $c->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    @if($c->order)
                                        <a href="{{ route('admin.orders.show', $c->order_id) }}"
                                            class="text-primary-600 hover:underline font-mono text-xs">#{{ $c->order->order_number ?? substr($c->order_id, 0, 8) }}</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="text-gray-700">{{ $c->order?->customer?->name ?? '—' }}</td>
                                <td class="text-gray-700">{{ $c->marketer?->name ?? '—' }}</td>
                                <td class="font-mono font-medium">{{ number_format($c->order_value_cents / 100, 2) }}</td>
                                <td class="font-mono text-green-700 font-medium bg-green-50">
                                    {{ number_format($c->commission_amount_cents / 100, 2) }}
                                </td>
                                <td class="font-mono text-amber-700 font-medium bg-amber-50">
                                    @php
                                        $totalRate = $c->total_commission_pct ?? ($promotion->total_commission_pct ?? 0);
                                        $marketerRate = $c->commission_rate ?? ($promotion->marketer_share_pct ?? 0);
                                        $adminRate = max(0, $totalRate - $marketerRate);
                                        $adminEarned = $c->order_value_cents * $adminRate / 100;
                                    @endphp
                                    {{ number_format($adminEarned / 100, 2) }}
                                </td>
                                <td>
                                    @if($c->completed_at)
                                        <span class="badge badge-success text-xs">{{ __('admin.secret_promotions.paid') }}</span>
                                    @else
                                        <span class="badge badge-warning text-xs">{{ __('admin.secret_promotions.pending') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($conversions->isEmpty())
                    <p class="text-center text-gray-400 italic py-8">{{ __('admin.secret_promotions.no_conversions_yet') }}</p>
                @endif
            </x-card>

        </div>

        {{-- ─── SIDEBAR ──────────────────────────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Status & timeline --}}
            <x-card>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('admin.secret_promotions.status_timeline') }}</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ __('admin.status') }}</span>
                        <span class="badge badge-{{ $promotion->status_color }}">{{ ucfirst($promotion->status) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">{{ __('admin.secret_promotions.created') }}</span>
                        <span class="text-sm font-medium">{{ $promotion->created_at->format('d M Y') }}</span>
                    </div>
                    @if($promotion->valid_until)
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">{{ __('admin.secret_promotions.expires') }}</span>
                            <span
                                class="text-sm font-medium {{ $promotion->isExpired() ? 'text-red-600' : ($promotion->valid_until->diffInDays() <= 7 ? 'text-amber-600' : '') }}">
                                {{ $promotion->valid_until->format('d M Y') }}
                                @if(!$promotion->isExpired())
                                    <span class="text-xs text-gray-400">({{ $promotion->valid_until->diffForHumans() }})</span>
                                @endif
                            </span>
                        </div>
                    @else
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">{{ __('admin.secret_promotions.expires') }}</span>
                            <span class="text-sm text-gray-400 italic">{{ __('admin.secret_promotions.never') }}</span>
                        </div>
                    @endif
                    @if($promotion->updated_at->ne($promotion->created_at))
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">{{ __('admin.secret_promotions.last_updated') }}</span>
                            <span class="text-sm text-gray-500">{{ $promotion->updated_at->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </x-card>

            {{-- Parties --}}
            <x-card>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('admin.secret_promotions.parties') }}</h3>

                {{-- Vendor --}}
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-10 h-10 rounded-full bg-blue-100 flex-shrink-0 flex items-center justify-center text-blue-700 font-bold text-sm">
                        {{ strtoupper(substr($promotion->vendor?->store_name ?? 'V', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">{{ __('admin.secret_promotions.vendor') }}</p>
                        <a href="{{ route('admin.vendors.show', $promotion->vendor_id) }}"
                            class="text-sm font-medium text-primary-600 hover:underline">
                            {{ $promotion->vendor?->store_name ?? '—' }}
                        </a>
                    </div>
                </div>

                {{-- Marketer --}}
                @if($promotion->marketer)
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-full bg-green-100 flex-shrink-0 flex items-center justify-center text-green-700 font-bold text-sm">
                            {{ strtoupper(substr($promotion->marketer->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase">{{ __('admin.secret_promotions.marketer') }}</p>
                            <a href="{{ route('admin.marketers.show', $promotion->marketer_id) }}"
                                class="text-sm font-medium text-primary-600 hover:underline">
                                {{ $promotion->marketer->name }}
                            </a>
                            <p class="text-xs text-gray-400 capitalize">{{ $promotion->marketer->type }}</p>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-full bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-500 text-lg">
                            &#9734;
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase">{{ __('admin.secret_promotions.marketer') }}</p>
                            <p class="text-sm font-medium text-gray-500 italic">{{ __('admin.secret_promotions.open_to_all_eligible') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Listing --}}
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden border border-gray-200">
                        @if($promotion->vendorListing?->product?->images?->first())
                            <img src="{{ $promotion->vendorListing->product->images->first()->url }}" alt=""
                                class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">IMG</div>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">{{ __('admin.secret_promotions.listing') }}</p>
                        <p class="text-sm font-medium text-gray-900">
                            {{ $promotion->vendorListing?->product?->name_en ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $promotion->listing_price_formatted }}</p>
                    </div>
                </div>
            </x-card>

            {{-- Visibility --}}
            <x-card>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('admin.secret_promotions.what_each_party_sees') }}</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 text-blue-500">🔵</span>
                        <div>
                            <p class="font-medium text-gray-700">{{ __('admin.secret_promotions.vendor_sees') }}</p>
                            <p class="text-gray-500">{{ __('admin.secret_promotions.total_commission') }}
                                <strong>{{ $promotion->total_commission_pct }}%</strong> — {{ __('admin.secret_promotions.no_split_detail') }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 text-green-500">🟢</span>
                        <div>
                            <p class="font-medium text-gray-700">{{ __('admin.secret_promotions.marketer_sees') }}</p>
                            <p class="text-gray-500">{{ __('admin.secret_promotions.their_share') }} <strong>{{ $promotion->marketer_share_pct }}%</strong> {{ __('admin.secret_promotions.their_share_only') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="mt-0.5 text-amber-500">🔒</span>
                        <div>
                            <p class="font-medium text-amber-700">{{ __('admin.secret_promotions.admin_only_colon') }}</p>
                            <p class="text-amber-600">{{ __('admin.secret_promotions.platform_earns') }} <strong>{{ $promotion->admin_share_pct }}%</strong>
                                {{ __('admin.secret_promotions.silently') }}</p>
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- Quick actions --}}
            <x-card>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('admin.secret_promotions.actions') }}</h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.secret-promotions.index') }}" class="btn btn-ghost btn-sm w-full">{{ __('admin.secret_promotions.back_to_list') }}</a>
                    <button type="button" id="sidebar-duplicate-btn" class="btn btn-ghost btn-sm w-full">{{ __('admin.secret_promotions.duplicate_promotion') }}</button>
                    @if($promotion->status !== 'expired')
                        <button type="button" id="sidebar-expire-btn" class="btn btn-danger btn-sm w-full">{{ __('admin.secret_promotions.force_expire') }}</button>
                    @endif
                </div>
            </x-card>

        </div>

    </div>

    {{-- ─── Edit Modal (reuse structure from index) ────────────────────────────── --}}
    <div id="edit-promo-modal" class="modal-backdrop hidden">
        <div class="modal-box max-w-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('admin.secret_promotions.edit_secret_promotion') }}</h3>
                <button type="button" data-modal-close
                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
            </div>
            <form id="edit-promo-form" data-update-url="{{ route('admin.secret-promotions.update', $promotion) }}">
                @method('PUT')
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">{{ __('admin.secret_promotions.marketer') }} <span class="text-xs text-gray-400 font-normal">{{ __('admin.secret_promotions.leave_empty_open_to_all') }}</span></label>
                            <select id="edit-marketer-select" name="marketer_id" class="form-input w-full text-sm">
                                <option value="">{{ __('admin.secret_promotions.open_to_all_eligible_marketers') }}</option>
                                @foreach($marketers as $m)
                                    <option value="{{ $m->id }}" @selected($promotion->marketer_id === $m->id)>{{ $m->name }}
                                        ({{ $m->type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">{{ __('admin.secret_promotions.valid_until_label') }}</label>
                            <input type="date" id="edit-valid-until" name="valid_until" class="form-input w-full text-sm"
                                value="{{ $promotion->valid_until?->format('Y-m-d') }}"
                                min="{{ now()->addDay()->format('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="form-label">{{ __('admin.secret_promotions.total_comm_pct') }}</label>
                            <input type="number" name="total_commission_pct" step="0.01" min="0.01" max="99"
                                class="form-input w-full text-sm" value="{{ $promotion->total_commission_pct }}">
                        </div>
                        <div>
                            <label class="form-label">{{ __('admin.secret_promotions.marketer_pct') }}</label>
                            <input type="number" name="marketer_share_pct" step="0.01" min="0.01"
                                class="form-input w-full text-sm" value="{{ $promotion->marketer_share_pct }}">
                        </div>
                        <div>
                            <label class="form-label">🔒 {{ __('admin.secret_promotions.product_value') }}</label>
                            <input type="number" name="product_value" step="0.01" min="0.01"
                                class="form-input w-full text-sm" value="{{ $promotion->product_value_cents / 100 }}">
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-gray-100">
                        <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.secret_promotions.save_changes') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="module">
        window.PROMO_ID = '{{ $promotion->id }}';
        window.PROMO_STATUS = '{{ $promotion->status }}';
        window.conversionChartData = @json($chartData);
        window.TOGGLE_STATUS_URL = '{{ route('admin.secret-promotions.toggle-status', $promotion) }}';
        window.EXPIRE_URL = '{{ route('admin.secret-promotions.expire', $promotion) }}';
        window.DUPLICATE_URL = '{{ route('admin.secret-promotions.duplicate', $promotion) }}';
        window.INDEX_URL = '{{ route('admin.secret-promotions.index') }}';
    </script>
    @vite('resources/js/admin/secret-promotion-detail.js')
@endpush
