@extends('layouts.admin')

@section('title', __('admin.vendor_campaign_offers.offer_title_prefix', ['name' => $offer->name]))

@section('content')

    {{-- ─── Header ─────────────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.vendor-campaign-offers.index') }}" class="hover:text-primary-600">{{ __('admin.vendor_campaign_offers.title') }}</a>
                <span>/</span>
                <span class="text-gray-800 font-medium truncate max-w-xs">{{ $offer->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $offer->name }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ \Illuminate\Support\Str::before(__('admin.vendor_campaign_offers.by_vendor_type_commission'), ':vendor') }}<strong>{{ $offer->vendor?->store_name ?? '—' }}</strong>{{ str_replace([':type', ':rate'], [ucwords(str_replace('_', ' ', $offer->campaign_type?->value)), number_format($offer->offered_commission_rate, 1)], \Illuminate\Support\Str::after(__('admin.vendor_campaign_offers.by_vendor_type_commission'), ':vendor')) }}
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @if($offer->status === \App\Enums\VendorCampaignOfferStatus::PendingAdmin)
                <button type="button"
                    class="btn btn-success js-approve-btn"
                    data-url="{{ route('admin.vendor-campaign-offers.approve', $offer->id) }}"
                    data-name="{{ e($offer->name) }}">
                    {{ __('admin.vendor_campaign_offers.approve_offer_btn') }}
                </button>
                <button type="button"
                    class="btn btn-danger"
                    id="open-reject-modal">
                    {{ __('admin.vendor_campaign_offers.reject_offer_btn') }}
                </button>
            @endif
            <a href="{{ route('admin.vendor-campaign-offers.index') }}" class="btn btn-secondary">← {{ __('admin.vendor_campaign_offers.back') }}</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ─── Left: Offer details ──────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Overview --}}
            <x-card>
                <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('admin.vendor_campaign_offers.offer_details') }}</h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.status_label') }}</dt>
                        <dd class="font-medium mt-0.5">
                            @php
                                $statusColors = ['pending_admin'=>'warning','active'=>'success','draft'=>'gray','paused'=>'gray','ended'=>'gray','cancelled'=>'danger'];
                                $c = $statusColors[$offer->status->value] ?? 'gray';
                                $label = $offer->status === \App\Enums\VendorCampaignOfferStatus::PendingAdmin
                                    ? __('admin.vendor_campaign_offers.pending_review_option')
                                    : __('admin.vendor_campaign_offers.' . $offer->status->value . '_option');
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700">{{ $label }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.commission_type_label') }}</dt>
                        <dd class="font-medium mt-0.5">{{ ucwords(str_replace('_', ' ', $offer->commission_type->value)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.date_range_label') }}</dt>
                        <dd class="font-medium mt-0.5">
                            {{ $offer->starts_at?->format('d M Y') }} – {{ $offer->ends_at?->format('d M Y') ?? '∞' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.invitation_deadline_label') }}</dt>
                        <dd class="font-medium mt-0.5">{{ $offer->invitation_deadline?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.budget_per_marketer') }}</dt>
                        <dd class="font-medium mt-0.5">
                            {{ $offer->budget_per_marketer ? '$' . number_format($offer->budget_per_marketer, 2) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.total_budget') }}</dt>
                        <dd class="font-medium mt-0.5">
                            {{ $offer->total_budget ? '$' . number_format($offer->total_budget, 2) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.attribution_model_label') }}</dt>
                        <dd class="font-medium mt-0.5">{{ ucwords(str_replace('_', ' ', $offer->attribution_model?->value ?? '')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.whatsapp_sharing_label') }}</dt>
                        <dd class="font-medium mt-0.5">{{ $offer->whatsapp_sharing_enabled ? __('admin.vendor_campaign_offers.yes') : __('admin.vendor_campaign_offers.no') }}</dd>
                    </div>
                    @if($offer->approved_at)
                        <div>
                            <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.approved_by_label') }}</dt>
                            <dd class="font-medium mt-0.5">{{ __('admin.vendor_campaign_offers.approved_by_on', ['name' => $offer->approvedByAdmin?->name ?? '—', 'date' => $offer->approved_at->format('d M Y')]) }}</dd>
                        </div>
                    @endif
                    @if($offer->rejection_reason)
                        <div class="col-span-2">
                            <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.rejection_reason_label') }}</dt>
                            <dd class="font-medium mt-0.5 text-danger-700">{{ $offer->rejection_reason }}</dd>
                        </div>
                    @endif
                </dl>

                @if($offer->description)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('admin.vendor_campaign_offers.description_label') }}</p>
                        <p class="text-sm text-gray-700">{{ $offer->description }}</p>
                    </div>
                @endif

                @if($offer->requirements)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('admin.vendor_campaign_offers.marketer_requirements') }}</p>
                        <p class="text-sm text-gray-700">{{ $offer->requirements }}</p>
                    </div>
                @endif
            </x-card>

            {{-- Products --}}
            <x-card>
                <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('admin.vendor_campaign_offers.products_heading', ['count' => $offer->products->count()]) }}</h2>
                @if($offer->products->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('admin.vendor_campaign_offers.no_products_attached') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-start text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                                    <th class="pb-2 pr-4">{{ __('admin.vendor_campaign_offers.position_column') }}</th>
                                    <th class="pb-2 pr-4">{{ __('admin.vendor_campaign_offers.product_column') }}</th>
                                    <th class="pb-2 pr-4">{{ __('admin.vendor_campaign_offers.commission_override_column') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($offer->products->sortBy('position') as $product)
                                    <tr>
                                        <td class="py-2 pr-4 text-gray-400">{{ $product->position }}</td>
                                        <td class="py-2 pr-4 font-medium">
                                            {{ $product->vendorListing?->product?->name ?? $product->vendor_listing_id }}
                                        </td>
                                        <td class="py-2 pr-4">
                                            {{ $product->commission_override ? number_format($product->commission_override, 1) . '%' : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

            {{-- Invitations --}}
            <x-card>
                <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('admin.vendor_campaign_offers.invitations_heading', ['count' => $offer->invitations->count()]) }}</h2>
                @if($offer->invitations->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('admin.vendor_campaign_offers.no_marketers_invited') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-start text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                                    <th class="pb-2 pr-4">{{ __('admin.vendor_campaign_offers.marketer_column') }}</th>
                                    <th class="pb-2 pr-4">{{ __('admin.vendor_campaign_offers.status_column') }}</th>
                                    <th class="pb-2 pr-4">{{ __('admin.vendor_campaign_offers.responded_column') }}</th>
                                    <th class="pb-2">{{ __('admin.vendor_campaign_offers.campaign_column') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($offer->invitations->sortBy('created_at') as $inv)
                                    @php
                                        $invColors = ['pending'=>'warning','accepted'=>'success','declined'=>'danger','expired'=>'gray','revoked'=>'gray'];
                                        $ic = $invColors[$inv->status->value] ?? 'gray';
                                    @endphp
                                    <tr>
                                        <td class="py-2 pr-4 font-medium">{{ $inv->marketer?->name ?? '—' }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $ic }}-100 text-{{ $ic }}-700">
                                                {{ __('admin.vendor_campaign_offers.invitation_status_' . $inv->status->value) }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-4 text-gray-500">
                                            {{ $inv->responded_at?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td class="py-2 text-gray-500">
                                            {{ $inv->resultingCampaign ? '#' . substr($inv->resultingCampaign->id, 0, 8) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>

        </div>

        {{-- ─── Right: Stats sidebar ─────────────────────────────────────────────────── --}}
        <div class="space-y-4">
            <x-card>
                <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wider">{{ __('admin.vendor_campaign_offers.conversion_stats') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.accepted_stat') }}</dt>
                        <dd class="font-semibold text-success-700">{{ $conversionStats['accepted'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.pending_stat') }}</dt>
                        <dd class="font-semibold text-warning-700">{{ $conversionStats['pending'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.declined_expired_stat') }}</dt>
                        <dd class="font-semibold text-gray-600">{{ $conversionStats['declined'] }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="text-gray-500">{{ __('admin.vendor_campaign_offers.total_conversions_stat') }}</dt>
                        <dd class="font-bold text-primary-700">{{ $conversionStats['conversions'] }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

    </div>

    {{-- ─── Reject modal ───────────────────────────────────────────────────────────── --}}
    <div id="reject-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('admin.vendor_campaign_offers.reject_offer_title') }}</h3>
            <p class="text-sm text-gray-500 mb-4">{{ __('admin.vendor_campaign_offers.reject_offer_subtitle') }}</p>
            <textarea id="reject-reason" rows="4" class="form-input w-full text-sm mb-4" placeholder="{{ __('admin.vendor_campaign_offers.rejection_reason_placeholder') }}"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" id="reject-cancel" class="btn btn-secondary">{{ __('admin.vendor_campaign_offers.cancel') }}</button>
                <button type="button" id="reject-confirm" class="btn btn-danger">{{ __('admin.vendor_campaign_offers.reject_offer_btn') }}</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const headers = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' };
    const T = {
        approveConfirm: @json(__('admin.vendor_campaign_offers.approve_confirm')),
        rejectionReasonRequired: @json(__('admin.vendor_campaign_offers.rejection_reason_required')),
    };

    // Approve
    document.addEventListener('click', e => {
        const btn = e.target.closest('.js-approve-btn');
        if (!btn) return;
        if (!confirm(T.approveConfirm.replace(':name', btn.dataset.name))) return;
        fetch(btn.dataset.url, { method: 'POST', headers })
            .then(r => r.json())
            .then(d => { alert(d.message); location.reload(); });
    });

    // Reject modal
    const modal    = document.getElementById('reject-modal');
    const reasonEl = document.getElementById('reject-reason');
    const rejectUrl = '{{ route('admin.vendor-campaign-offers.reject', $offer->id) }}';

    document.getElementById('open-reject-modal')?.addEventListener('click', () => {
        reasonEl.value = '';
        modal.classList.remove('hidden');
    });

    document.getElementById('reject-cancel').addEventListener('click', () => modal.classList.add('hidden'));

    document.getElementById('reject-confirm').addEventListener('click', () => {
        const reason = reasonEl.value.trim();
        if (!reason) { alert(T.rejectionReasonRequired); return; }
        fetch(rejectUrl, { method: 'POST', headers, body: JSON.stringify({ rejection_reason: reason }) })
            .then(r => r.json())
            .then(d => { alert(d.message); modal.classList.add('hidden'); location.reload(); });
    });
})();
</script>
@endpush
