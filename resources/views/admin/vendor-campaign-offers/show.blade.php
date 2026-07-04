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
                {{ \Illuminate\Support\Str::before(__('admin.vendor_campaign_offers.by_vendor_type_commission'), ':vendor') }}<strong>{{ $offer->vendor?->store_name ?? '—' }}</strong>{{ str_replace([':type', ':rate'], [ucwords(str_replace('_', ' ', $offer->campaign_type)), number_format($offer->offered_commission_rate, 1)], \Illuminate\Support\Str::after(__('admin.vendor_campaign_offers.by_vendor_type_commission'), ':vendor')) }}
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            @if($offer->status === 'pending_admin')
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
                <h2 class="text-base font-semibold text-gray-900 mb-4">Offer Details</h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Status</dt>
                        <dd class="font-medium mt-0.5">
                            @php
                                $statusColors = ['pending_admin'=>'warning','active'=>'success','draft'=>'gray','paused'=>'gray','ended'=>'gray','cancelled'=>'danger'];
                                $c = $statusColors[$offer->status] ?? 'gray';
                                $label = ucwords(str_replace(['_','admin'],[ ' ','Review'], $offer->status));
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700">{{ $label }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Commission Type</dt>
                        <dd class="font-medium mt-0.5">{{ ucwords(str_replace('_', ' ', $offer->commission_type)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Date Range</dt>
                        <dd class="font-medium mt-0.5">
                            {{ $offer->starts_at?->format('d M Y') }} – {{ $offer->ends_at?->format('d M Y') ?? '∞' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Invitation Deadline</dt>
                        <dd class="font-medium mt-0.5">{{ $offer->invitation_deadline?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Budget / Marketer</dt>
                        <dd class="font-medium mt-0.5">
                            {{ $offer->budget_per_marketer_cents ? '$' . number_format($offer->budget_per_marketer_cents / 100, 2) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Total Budget</dt>
                        <dd class="font-medium mt-0.5">
                            {{ $offer->total_budget_cents ? '$' . number_format($offer->total_budget_cents / 100, 2) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Attribution Model</dt>
                        <dd class="font-medium mt-0.5">{{ ucwords(str_replace('_', ' ', $offer->attribution_model)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">WhatsApp Sharing</dt>
                        <dd class="font-medium mt-0.5">{{ $offer->whatsapp_sharing_enabled ? 'Yes' : 'No' }}</dd>
                    </div>
                    @if($offer->approved_at)
                        <div>
                            <dt class="text-gray-500">Approved by</dt>
                            <dd class="font-medium mt-0.5">{{ $offer->approvedByAdmin?->name ?? '—' }} on {{ $offer->approved_at->format('d M Y') }}</dd>
                        </div>
                    @endif
                    @if($offer->rejection_reason)
                        <div class="col-span-2">
                            <dt class="text-gray-500">Rejection Reason</dt>
                            <dd class="font-medium mt-0.5 text-danger-700">{{ $offer->rejection_reason }}</dd>
                        </div>
                    @endif
                </dl>

                @if($offer->description)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Description</p>
                        <p class="text-sm text-gray-700">{{ $offer->description }}</p>
                    </div>
                @endif

                @if($offer->requirements)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Marketer Requirements</p>
                        <p class="text-sm text-gray-700">{{ $offer->requirements }}</p>
                    </div>
                @endif
            </x-card>

            {{-- Products --}}
            <x-card>
                <h2 class="text-base font-semibold text-gray-900 mb-4">Products ({{ $offer->products->count() }})</h2>
                @if($offer->products->isEmpty())
                    <p class="text-sm text-gray-400">No products attached.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-start text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                                    <th class="pb-2 pr-4">#</th>
                                    <th class="pb-2 pr-4">Product</th>
                                    <th class="pb-2 pr-4">Commission Override</th>
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
                <h2 class="text-base font-semibold text-gray-900 mb-4">Invitations ({{ $offer->invitations->count() }})</h2>
                @if($offer->invitations->isEmpty())
                    <p class="text-sm text-gray-400">No marketers have been invited yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-start text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">
                                    <th class="pb-2 pr-4">Marketer</th>
                                    <th class="pb-2 pr-4">Status</th>
                                    <th class="pb-2 pr-4">Responded</th>
                                    <th class="pb-2">Campaign</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($offer->invitations->sortBy('created_at') as $inv)
                                    @php
                                        $invColors = ['pending'=>'warning','accepted'=>'success','declined'=>'danger','expired'=>'gray','revoked'=>'gray'];
                                        $ic = $invColors[$inv->status] ?? 'gray';
                                    @endphp
                                    <tr>
                                        <td class="py-2 pr-4 font-medium">{{ $inv->marketer?->name ?? '—' }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $ic }}-100 text-{{ $ic }}-700">
                                                {{ ucfirst($inv->status) }}
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
                <h2 class="text-sm font-semibold text-gray-700 mb-4 uppercase tracking-wider">Conversion Stats</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Accepted</dt>
                        <dd class="font-semibold text-success-700">{{ $conversionStats['accepted'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Pending</dt>
                        <dd class="font-semibold text-warning-700">{{ $conversionStats['pending'] }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Declined / Expired</dt>
                        <dd class="font-semibold text-gray-600">{{ $conversionStats['declined'] }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-3">
                        <dt class="text-gray-500">Total Conversions</dt>
                        <dd class="font-bold text-primary-700">{{ $conversionStats['conversions'] }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

    </div>

    {{-- ─── Reject modal ───────────────────────────────────────────────────────────── --}}
    <div id="reject-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Reject Campaign Offer</h3>
            <p class="text-sm text-gray-500 mb-4">Provide a reason. The vendor will see this and can fix and resubmit.</p>
            <textarea id="reject-reason" rows="4" class="form-input w-full text-sm mb-4" placeholder="Rejection reason…"></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" id="reject-cancel" class="btn btn-secondary">Cancel</button>
                <button type="button" id="reject-confirm" class="btn btn-danger">Reject Offer</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function () {
    const headers = { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' };

    // Approve
    document.addEventListener('click', e => {
        const btn = e.target.closest('.js-approve-btn');
        if (!btn) return;
        if (!confirm(`Approve "${btn.dataset.name}"? Marketers will be notified.`)) return;
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
        if (!reason) { alert('Please enter a rejection reason.'); return; }
        fetch(rejectUrl, { method: 'POST', headers, body: JSON.stringify({ rejection_reason: reason }) })
            .then(r => r.json())
            .then(d => { alert(d.message); modal.classList.add('hidden'); location.reload(); });
    });
})();
</script>
@endpush
