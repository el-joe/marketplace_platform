@extends('layouts.admin')

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', $marketer->name)

@section('content')

{{-- ─── Breadcrumb ──────────────────────────────────────────────────────────── --}}

{{-- ─── Profile Card ────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl border border-gray-200 p-6">
        <div class="flex items-center gap-4 mb-5">
            @if($marketer->profile_photo_path)
                <img src="{{ asset('storage/' . $marketer->profile_photo_path) }}"
                     class="w-16 h-16 rounded-full object-cover">
            @else
                <div class="w-16 h-16 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($marketer->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $marketer->name }}</h2>
                <span class="badge badge-{{ $marketer->status_color }}">{{ $marketer->status->label() }}</span>
                <x-marketer-type-badge :type="$marketer->type" class="ml-1" />
            </div>
        </div>

        <div class="space-y-2 text-sm">
            @foreach([
                __('admin.marketers.email_label')   => $marketer->email,
                __('admin.marketers.phone_label')   => $marketer->phone ?? '—',
                __('admin.marketers.country_label') => $marketer->country?->name_en ?? '—',
                __('admin.marketers.niche_label')   => $marketer->niche ?? '—',
                __('admin.marketers.ref_code')       => $marketer->referral_code,
                __('admin.marketers.joined')         => $marketer->created_at->format('d M Y'),
            ] as $label => $value)
                <div class="flex justify-between gap-2">
                    <span class="text-gray-400">{{ $label }}</span>
                    <span class="font-medium text-gray-700 text-end">{{ $value }}</span>
                </div>
            @endforeach
        </div>

        {{-- Social links --}}
        @php
            $socials = [
                'instagram' => $marketer->social_instagram,
                'tiktok'    => $marketer->social_tiktok,
                'youtube'   => $marketer->social_youtube,
                'twitter'   => $marketer->social_twitter,
                'facebook'  => $marketer->social_facebook,
            ];
        @endphp
        @if(array_filter($socials))
            <div class="flex gap-2 mt-4 pt-4 border-t border-gray-100">
                @foreach($socials as $platform => $handle)
                    @if($handle)
                        <a href="{{ $handle }}" target="_blank"
                           class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg px-2 py-1 transition-colors capitalize">
                            {{ $platform }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- Action buttons --}}
        <div class="flex gap-2 mt-5 pt-4 border-t border-gray-100">
            @if($marketer->status === \App\Enums\MarketerStatus::Pending)
                <button type="button" class="btn btn-success btn-sm flex-1" id="btn-approve">{{ __('admin.marketers.approve') }}</button>
                <button type="button" class="btn btn-danger btn-sm flex-1" id="btn-reject">{{ __('admin.marketers.reject') }}</button>
            @elseif($marketer->status === \App\Enums\MarketerStatus::Active)
                <button type="button" class="btn btn-warning btn-sm flex-1" id="btn-suspend">{{ __('admin.marketers.suspend') }}</button>
            @elseif($marketer->status === \App\Enums\MarketerStatus::Suspended)
                <button type="button" class="btn btn-success btn-sm flex-1" id="btn-activate">{{ __('admin.marketers.activate') }}</button>
            @endif
        </div>

        <div class="mt-2">
            <button type="button" class="btn btn-primary btn-sm w-full" id="btn-send-invitation">
                📣 {{ __('admin.marketer_show_section.send_campaign_invitation') }}
            </button>
        </div>
    </div>

    {{-- Stats Column --}}
    <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-3 gap-4 content-start">
        @foreach([
            [__('admin.marketers.campaigns'),        $stats['total_campaigns'],    'total'],
            [__('admin.marketers.active_campaigns'), $stats['active_campaigns'],   'active'],
            [__('admin.marketers.conversions'),      $stats['total_conversions'],  'conversions'],
            [__('admin.marketers.followers'),        number_format($marketer->followers_count), 'followers'],
            [__('admin.marketers.total_clicks'),     number_format($marketer->total_clicks),    'clicks'],
            [__('admin.marketers.comm_rate'),        $marketer->commission_rate . '%',          'rate'],
        ] as [$label, $value, $key])
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-800">{{ $value }}</p>
            </div>
        @endforeach

        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
            <p class="text-xs font-medium text-yellow-600 uppercase tracking-wide">{{ __('admin.marketers.pending_earnings') }}</p>
            @forelse($stats['pending_by_currency'] as $currency => $cents)
                <p class="mt-1 text-xl font-bold text-yellow-700">{{ number_format($cents / 100, 2) }} {{ $currency }}</p>
            @empty
                <p class="mt-1 text-2xl font-bold text-yellow-700">—</p>
            @endforelse
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-xs font-medium text-green-600 uppercase tracking-wide">{{ __('admin.marketers.paid_earnings') }}</p>
            @forelse($stats['paid_by_currency'] as $currency => $cents)
                <p class="mt-1 text-xl font-bold text-green-700">{{ number_format($cents / 100, 2) }} {{ $currency }}</p>
            @empty
                <p class="mt-1 text-2xl font-bold text-green-700">—</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ─── Tabs ────────────────────────────────────────────────────────────────── --}}
@php
    $tabLabels = $marketer->isAffiliate()
        ? [
            'overview'    => '📋 ' . __('admin.marketers.tab_overview'),
            'campaigns'   => __('admin.marketers.tab_campaigns'),
            'promo_codes' => '🏷️ ' . __('admin.marketer_show_section.tab_promo_codes'),
            'conversions' => __('admin.marketers.tab_conversions'),
            'payouts'     => '💳 ' . __('admin.marketers.tab_payouts'),
        ]
        : [
            'overview'     => '📋 ' . __('admin.marketers.tab_overview'),
            'deals'        => '🤝 ' . __('admin.marketer_show_section.tab_deals'),
            'deliverables' => '📄 ' . __('admin.marketer_show_section.tab_deliverables'),
            'media_kit'    => '🪪 ' . __('admin.marketer_show_section.tab_media_kit'),
            'payouts'      => '💳 ' . __('admin.marketers.tab_payouts'),
        ];
@endphp
<div x-data="{ tab: 'overview' }"
     x-effect="document.dispatchEvent(new CustomEvent('marketer-tab-change', { detail: tab }))">

    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 mb-5 flex-wrap">
        @foreach($tabLabels as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                    class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-colors"
                    :class="tab === '{{ $key }}' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Overview --}}
    <div x-show="tab === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.marketers.commission_tiers_title') }}</h3>
            <p class="text-sm text-gray-500 mb-3">{{ __('admin.marketers.current_sales') }}: <strong>{{ $stats['total_conversions'] }}</strong></p>
            @php $tiers = $marketer->commissionTiers()->whereNull('campaign_id')->orderBy('tier_order')->get(); @endphp
            @if($tiers->isEmpty())
                <p class="text-sm text-gray-400 italic">{{ __('admin.marketers.no_tiers_configured', ['rate' => $marketer->commission_rate]) }}</p>
            @else
                <div class="space-y-2">
                    @foreach($tiers as $tier)
                        @php $isCurrent = $stats['total_conversions'] >= $tier->min_sales_count && ($tier->max_sales_count === null || $stats['total_conversions'] <= $tier->max_sales_count); @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl {{ $isCurrent ? 'bg-primary-50 border-2 border-primary-300' : 'bg-gray-50 border border-gray-200' }}">
                            <div class="w-7 h-7 rounded-full {{ $isCurrent ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-600' }} flex items-center justify-center font-bold text-xs">{{ $tier->tier_order }}</div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold">{{ __('admin.marketers.sales_range', ['min' => number_format($tier->min_sales_count), 'max' => $tier->max_sales_count ? number_format($tier->max_sales_count) : __('admin.marketers.unlimited_sales')]) }}</p>
                                <p class="text-xs text-gray-500">{{ __('admin.marketers.commission') }}: <strong>{{ $tier->commission_rate }}%</strong></p>
                            </div>
                            @if($isCurrent)<span class="badge badge-primary">{{ __('admin.marketers.current_tier') }}</span>@endif
                        </div>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('admin.marketers.all.tiers.show', $marketer) }}" class="btn btn-primary btn-sm mt-4">{{ __('admin.marketers.edit_tiers') }}</a>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">📦 {{ __('admin.marketers.tab_samples') }}</h3>
                <div class="overflow-x-auto">
                <table id="marketer-samples-table" class="w-full text-sm" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('admin.marketers.sample_vendor') }}</th>
                            <th>{{ __('admin.status') }}</th>
                            <th>{{ __('admin.marketers.sample_date') }}</th>
                            <th>{{ __('admin.marketers.sample_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">🔒 {{ __('admin.marketers.tab_secret_promos') }}</h3>
                <div class="overflow-x-auto">
                <table id="marketer-secret-promos-table" class="w-full text-sm" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('admin.marketers.vendor') }} / {{ __('admin.marketers.product') }}</th>
                            <th>{{ __('admin.marketers.total_pct') }}</th>
                            <th>{{ __('admin.status') }}</th>
                            <th>{{ __('admin.marketers.valid_until') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">📣 {{ __('admin.marketer_show_section.admin_campaign_invitations') }}</h3>
                <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-400 text-xs uppercase">
                            <th class="pb-2">{{ __('admin.marketer_show_section.title_col') }}</th>
                            <th class="pb-2">{{ __('admin.marketer_show_section.type_col') }}</th>
                            <th class="pb-2">{{ __('admin.marketer_show_section.rate_col') }}</th>
                            <th class="pb-2">{{ __('admin.marketer_show_section.status_col') }}</th>
                            <th class="pb-2">{{ __('admin.marketer_show_section.sent_at_col') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adminInvitations as $inv)
                            @php
                                $statusColors = [
                                    'pending' => 'warning', 'accepted' => 'success',
                                    'declined' => 'danger', 'expired' => 'secondary', 'revoked' => 'secondary',
                                ];
                            @endphp
                            <tr class="border-t border-gray-100">
                                <td class="py-2">{{ $inv->title }}</td>
                                <td class="py-2">{{ $inv->campaign_type->label() }}</td>
                                <td class="py-2">{{ number_format($inv->commission_rate_percent, 2) }}%</td>
                                <td class="py-2"><span class="badge badge-{{ $statusColors[$inv->status->value] ?? 'secondary' }}">{{ $inv->status->label() }}</span></td>
                                <td class="py-2 text-gray-500">{{ $inv->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-4 text-center text-gray-400 italic">{{ __('admin.marketer_show_section.no_invitations_sent') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Payouts DataTable (both types) --}}
    <div x-show="tab === 'payouts'">
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-payouts-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketer_show_section.payout_number') }}</th>
                        <th>{{ __('admin.marketer_show_section.period') }}</th>
                        <th>{{ __('admin.marketers.conv') }}</th>
                        <th>{{ __('admin.marketer_show_section.net_amount') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketer_show_section.processed_at') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

    {{-- Affiliate-only tabs --}}
    @if($marketer->isAffiliate())
    {{-- Campaigns DataTable --}}
    <div x-show="tab === 'campaigns'">
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-campaigns-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketers.campaign') }}</th>
                        <th>{{ __('admin.marketers.type') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketers.clicks') }}</th>
                        <th>{{ __('admin.marketers.conv') }}</th>
                        <th>{{ __('admin.marketers.revenue') }}</th>
                        <th>{{ __('admin.marketers.created') }}</th>
                        <th>{{ __('admin.marketers.actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

    {{-- Conversions DataTable --}}
    <div x-show="tab === 'conversions'">
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-conversions-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketers.marketer') }}</th>
                        <th>{{ __('admin.marketers.campaign') }}</th>
                        <th>{{ __('admin.marketers.order_value') }}</th>
                        <th>{{ __('admin.marketers.commission') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketers.date') }}</th>
                        <th>{{ __('admin.marketers.bulk') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

    <div x-show="tab === 'promo_codes'">
        <div class="flex justify-end mb-3">
            <button type="button" id="create-marketer-promo-btn" class="btn btn-primary btn-sm">{{ __('admin.marketer_show_section.create_promo_code') }}</button>
        </div>
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-promo-codes-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketer_show_section.code_col') }}</th>
                        <th>{{ __('admin.marketer_show_section.marketer_col') }}</th>
                        <th>{{ __('admin.marketer_show_section.discount_col') }}</th>
                        <th>{{ __('admin.marketer_show_section.uses_col') }}</th>
                        <th>{{ __('admin.marketer_show_section.revenue_generated_col') }}</th>
                        <th>{{ __('admin.marketer_show_section.commission_earned_col') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketer_show_section.valid_until_col') }}</th>
                        <th>{{ __('admin.marketer_show_section.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

    {{-- Create Promo Code Modal (marketer-scoped) --}}
    <div id="create-marketer-promo-modal" class="modal-backdrop hidden">
        <div class="modal-box max-w-xl">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('admin.marketer_show_section.create_promo_code_for', ['name' => $marketer->name]) }}</h3>
                <button type="button" data-modal-close class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
            </div>
            <form id="create-marketer-promo-form">
                <input type="hidden" name="marketer_id" value="{{ $marketer->id }}">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">{{ __('admin.marketer_show_section.code_label') }}</label>
                        <input type="text" name="code" class="form-input w-full" placeholder="{{ __('admin.marketer_show_section.code_placeholder') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.discount_type') }} <span class="text-red-500">*</span></label>
                        <select name="discount_type" class="form-input w-full" required>
                            <option value="percentage">{{ __('admin.marketer_show_section.discount_type_percentage') }}</option>
                            <option value="fixed_amount">{{ __('admin.marketer_show_section.discount_type_fixed_amount') }}</option>
                            <option value="free_shipping">{{ __('admin.marketer_show_section.discount_type_free_shipping') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.discount_value') }} <span class="text-red-500">*</span></label>
                        <input type="number" name="discount_value" class="form-input w-full" step="0.01" min="0" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.currency_label') }}</label>
                        <input type="text" name="currency" class="form-input w-full" maxlength="3" placeholder="{{ __('admin.marketer_show_section.currency_placeholder') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.max_uses') }}</label>
                        <input type="number" name="max_uses" class="form-input w-full" min="1" placeholder="{{ __('admin.marketer_show_section.max_uses_placeholder') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.min_order_amount') }}</label>
                        <input type="number" name="min_order_amount" class="form-input w-full" min="0" placeholder="{{ __('admin.marketer_show_section.min_order_amount_placeholder') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.valid_from') }} <span class="text-red-500">*</span></label>
                        <input type="date" name="valid_from" class="form-input w-full" required>
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">{{ __('admin.marketer_show_section.valid_until') }} <span class="text-red-500">*</span></label>
                        <input type="date" name="valid_until" class="form-input w-full" required>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                    <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.marketer_show_section.cancel') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.marketer_show_section.create_promo_code') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Send Campaign Invitation Modal --}}
    <div id="send-invitation-modal" class="modal-backdrop hidden">
        <div class="modal-box max-w-xl">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('admin.marketer_show_section.send_invitation_to', ['name' => $marketer->name]) }}</h3>
                <button type="button" data-modal-close class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
            </div>
            <form id="send-invitation-form">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="form-label">{{ __('admin.marketer_show_section.title_field') }} <span class="text-red-500">*</span></label>
                        <input type="text" name="title" class="form-input w-full" maxlength="255" required>
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">{{ __('admin.marketer_show_section.description_field') }}</label>
                        <textarea name="description" class="form-input w-full" rows="3"></textarea>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.campaign_type_field') }} <span class="text-red-500">*</span></label>
                        <select name="campaign_type" class="form-input w-full" required>
                            @foreach($campaignTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.commission_type_field') }} <span class="text-red-500">*</span></label>
                        <select name="commission_type" class="form-input w-full" required>
                            @foreach($commissionTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.commission_rate_field') }} <span class="text-red-500">*</span></label>
                        <input type="number" name="offered_commission_rate_pct" class="form-input w-full" step="0.01" min="0.01" max="100" required
                               placeholder="{{ __('admin.marketer_show_section.commission_rate_placeholder') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.budget_field') }}</label>
                        <input type="number" name="budget" class="form-input w-full" min="0" placeholder="{{ __('admin.marketer_show_section.budget_optional') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.budget_currency_field') }}</label>
                        <select name="budget_currency" class="form-input w-full">
                            <option value="">—</option>
                            @foreach($currencies as $code)
                                <option value="{{ $code }}">{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.starts_at_field') }}</label>
                        <input type="date" name="starts_at" class="form-input w-full">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketer_show_section.ends_at_field') }}</label>
                        <input type="date" name="ends_at" class="form-input w-full">
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">{{ __('admin.marketer_show_section.response_deadline_field') }}</label>
                        <input type="date" name="expires_at" class="form-input w-full">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                    <button type="button" data-modal-close class="btn btn-ghost btn-sm">{{ __('admin.marketer_show_section.cancel') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('admin.marketer_show_section.send_invitation') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Influencer-only tabs --}}
    @if($marketer->isInfluencer())
    <div x-show="tab === 'deals'">
        <div class="bg-white rounded-2xl border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.marketer_show_section.tab_deals') }}</h3>
            @forelse($marketer->deals as $deal)
                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-200 mb-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $deal->deal_name }}</p>
                        <p class="text-xs text-gray-500">{{ $deal->vendor?->store_name ?? '—' }} · {{ ucfirst(str_replace('_', ' ', $deal->deal_type)) }}</p>
                    </div>
                    <span class="badge badge-secondary">{{ ucfirst($deal->status) }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 italic">{{ __('admin.marketer_show_section.no_deals_yet') }}</p>
            @endforelse
            <a href="{{ route('admin.influencer-deals.index') }}?filter_marketer={{ $marketer->id }}" class="text-sm text-primary-600 hover:underline mt-3 inline-block">{{ __('admin.marketer_show_section.view_all_deals') }}</a>
        </div>
    </div>

    <div x-show="tab === 'deliverables'">
        <x-card>
            <div class="overflow-x-auto">
            <table id="marketer-deliverables-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>{{ __('admin.marketer_show_section.deal_col') }}</th>
                        <th>{{ __('admin.marketer_show_section.platform_col') }}</th>
                        <th>{{ __('admin.marketer_show_section.content_type_col') }}</th>
                        <th>{{ __('admin.status') }}</th>
                        <th>{{ __('admin.marketer_show_section.due_col') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            </div>
        </x-card>
    </div>

    <div x-show="tab === 'media_kit'">
        <div class="bg-white rounded-2xl border border-gray-200 p-6 max-w-lg">
            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ __('admin.marketer_show_section.media_kit_title') }}</h3>
            @if($marketer->mediaKit)
                <div class="space-y-2 text-sm">
                    <p class="font-semibold">{{ $marketer->mediaKit->headline }}</p>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('admin.marketer_show_section.avg_post_reach') }}</span><span class="font-medium">{{ number_format($marketer->mediaKit->avg_post_reach ?? 0) }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('admin.marketer_show_section.rate_per_post') }}</span><span class="font-medium">{{ $marketer->mediaKit->rate_per_post ? number_format($marketer->mediaKit->rate_per_post / 100, 2) . ' ' . $marketer->mediaKit->rate_currency : '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-400">{{ __('admin.marketer_show_section.visible_to_vendors') }}</span><span class="font-medium">{{ $marketer->mediaKit->is_visible_to_vendors ? __('admin.marketer_show_section.yes') : __('admin.marketer_show_section.no') }}</span></div>
                </div>
            @else
                <p class="text-sm text-gray-400 italic">{{ __('admin.marketer_show_section.no_media_kit') }}</p>
            @endif
        </div>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script>
    window.TRANSLATIONS = window.TRANSLATIONS || {};
    Object.assign(window.TRANSLATIONS, {
        approveMarketerConfirm: @json(__('admin.marketers.approve_campaign_confirm')),
        approve: @json(__('admin.marketers.approve')),
        rejectionReasonPrompt: @json(__('admin.marketers.rejection_reason_prompt')),
        suspendMarketerConfirm: @json(__('admin.marketers.suspend_marketer_confirm_title')),
        errorGeneric: @json(__('admin.marketers.error_generic')),
    });
</script>
<script type="module">
$(function () {
    const markId = '{{ $marketer->id }}';
    const tok    = '{{ csrf_token() }}';

    const dtConfig = (url, columns, order) => ({
        processing: true,
        serverSide: true,
        ajax: { url, type: 'POST', headers: { 'X-CSRF-TOKEN': tok } },
        columns,
        order,
        pageLength: 10,
    });

    const inited = {};

    function initTab(tab) {
        if (inited[tab]) return;
        inited[tab] = true;

        if (tab === 'overview') {
            $('#marketer-samples-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-samples.datatable', $marketer->id) }}',
                [{}, {}, {}, { orderable: false }],
                [[2, 'desc']]
            ));
            $('#marketer-secret-promos-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-secret-promotions.datatable', $marketer->id) }}',
                [{}, {}, {}, {}],
                [[3, 'desc']]
            ));
        } else if (tab === 'payouts') {
            $('#marketer-payouts-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-payouts.datatable', $marketer->id) }}',
                [{}, {}, {}, {}, {}, {}],
                [[1, 'desc']]
            ));
        } else if (tab === 'campaigns') {
            $('#marketer-campaigns-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-campaigns.datatable', $marketer->id) }}',
                [{}, {}, {}, {}, {}, {}, {}, { orderable: false }],
                [[6, 'desc']]
            ));
        } else if (tab === 'conversions') {
            $('#marketer-conversions-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-conversions.datatable', $marketer->id) }}',
                [{}, {}, {}, {}, {}, {}, { orderable: false }],
                [[5, 'desc']]
            ));
        } else if (tab === 'deliverables') {
            $('#marketer-deliverables-table').DataTable(dtConfig(
                '{{ route('admin.marketers.all.marketer-deliverables.datatable', $marketer->id) }}',
                [{}, {}, {}, {}, {}],
                [[4, 'asc']]
            ));
        } else if (tab === 'promo_codes') {
            $('#marketer-promo-codes-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('admin.affiliate-promo-codes.datatable') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok },
                    data: function (d) { d.filter_marketer = markId; },
                },
                columns: [{}, {}, {}, {}, {}, {}, {}, {}, { orderable: false, searchable: false }],
                order: [[7, 'desc']],
                pageLength: 10,
            });
        }
    }

    // Initialize the default tab on load, then lazily init others on switch.
    initTab('overview');

    document.addEventListener('marketer-tab-change', function (e) {
        initTab(e.detail);
    });

    // ── Approve / Reject / Suspend / Activate ─────────────────────────────────
    $('#btn-approve').on('click', function () {
        window.confirmDialog({ title: window.TRANSLATIONS.approveMarketerConfirm, confirmText: window.TRANSLATIONS.approve, onConfirm: () => {
            $.post('/marketers/' + markId + '/approve', { _token: tok })
                .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
        }});
    });
    $('#btn-reject').on('click', function () {
        const reason = prompt(window.TRANSLATIONS.rejectionReasonPrompt);
        if (!reason) return;
        $.post('/marketers/' + markId + '/reject', { _token: tok, reason })
            .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });
    $('#btn-suspend').on('click', function () {
        window.confirmDialog({ title: window.TRANSLATIONS.suspendMarketerConfirm, onConfirm: () => {
            $.post('/marketers/' + markId + '/suspend', { _token: tok })
                .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
        }});
    });
    $('#btn-activate').on('click', function () {
        $.post('/marketers/' + markId + '/activate', { _token: tok })
            .done(r => { window.Toast.success(r.message); setTimeout(() => location.reload(), 1200); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });

    // ── Promo codes (delegated) ────────────────────────────────────────────────
    $('#create-marketer-promo-btn').on('click', () => $('#create-marketer-promo-modal').removeClass('hidden'));
    $('#create-marketer-promo-modal [data-modal-close]').on('click', () => $('#create-marketer-promo-modal').addClass('hidden'));

    $('#create-marketer-promo-form').on('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());

        $.ajax({
            url: '{{ route('admin.affiliate-promo-codes.store') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data,
        })
            .done(r => {
                window.Toast.success(r.message);
                $('#create-marketer-promo-modal').addClass('hidden');
                this.reset();
                $('#marketer-promo-codes-table').DataTable().ajax.reload();
            })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });

    $(document).on('click', '.btn-toggle-promo', function () {
        const id = $(this).data('id');
        $.post('{{ url('affiliate-promo-codes') }}/' + id + '/toggle', { _token: tok })
            .done(r => { window.Toast.success(r.message); $('#marketer-promo-codes-table').DataTable().ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });

    $(document).on('click', '.btn-disable-promo', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: window.TRANSLATIONS.disablePromoConfirm, onConfirm: () => {
            $.ajax({
                url: '{{ url('affiliate-promo-codes') }}/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': tok },
            })
                .done(r => { window.Toast.success(r.message); $('#marketer-promo-codes-table').DataTable().ajax.reload(); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
        }});
    });

    // ── Send Campaign Invitation ───────────────────────────────────────────────
    $('#btn-send-invitation').on('click', () => $('#send-invitation-modal').removeClass('hidden'));
    $('#send-invitation-modal [data-modal-close]').on('click', () => $('#send-invitation-modal').addClass('hidden'));

    $('#send-invitation-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const data = Object.fromEntries(new FormData(form).entries());

        // Convert the human-entered percentage into basis points (5 -> 500)
        data.offered_commission_rate = Math.round(parseFloat(data.offered_commission_rate_pct || '0') * 100);
        delete data.offered_commission_rate_pct;
        if (!data.budget) delete data.budget_currency;

        $.ajax({
            url: '{{ route('admin.marketers.all.invite', $marketer->id) }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data,
        })
            .done(r => {
                window.Toast.success(r.message);
                $('#send-invitation-modal').addClass('hidden');
                form.reset();
                setTimeout(() => location.reload(), 1200);
            })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });

    // ── Sample datatable actions (delegated) ──────────────────────────────────
    $(document).on('click', '.btn-approve-sample', function () {
        const id = $(this).data('id');
        $.post('{{ url('marketer-samples') }}/' + id + '/approve', { _token: tok })
            .done(r => { window.Toast.success(r.message); $('#marketer-samples-table').DataTable().ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });
    $(document).on('click', '.btn-dispatch-sample', function () {
        const id = $(this).data('id');
        $.post('{{ url('marketer-samples') }}/' + id + '/dispatch', { _token: tok })
            .done(r => { window.Toast.success(r.message); $('#marketer-samples-table').DataTable().ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.errorGeneric));
    });
});
</script>
@endpush
