@extends('layouts.partner')

@section('title', __('partner.marketer_samples.title'))
@section('page-title', __('partner.marketer_samples.title'))

@section('content')

<div class="space-y-4">

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            [__('partner.marketer_samples.total_requests'), $stats['total'],      'bg-gray-50 text-gray-700'],
            [__('partner.marketer_samples.pending'),   $stats['requested'],  'bg-yellow-50 text-yellow-700'],
            [__('partner.marketer_samples.approved'),    $stats['approved'],   'bg-green-50 text-green-700'],
            [__('partner.marketer_samples.dispatched'),       $stats['dispatched'], 'bg-blue-50 text-blue-700'],
        ] as [$label, $count, $classes])
            <div class="rounded-2xl border border-gray-200 p-4 {{ $classes }}">
                <p class="text-xs font-medium uppercase tracking-wide opacity-70">{{ $label }}</p>
                <p class="text-2xl font-bold mt-1">{{ $count }}</p>
            </div>
        @endforeach
    </div>

    {{-- List --}}
    @if($requests->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
            <p class="text-4xl mb-3">📦</p>
            <p class="font-semibold text-gray-700">{{ __('partner.marketer_samples.no_requests') }}</p>
            <p class="text-sm text-gray-400 mt-1">{{ __('partner.marketer_samples.no_requests_hint') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($requests as $req)
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                    @if($req->status === \App\Enums\MarketerSampleRequestStatus::Requested)  bg-yellow-100 text-yellow-700
                                    @elseif($req->status === \App\Enums\MarketerSampleRequestStatus::Approved) bg-green-100 text-green-700
                                    @elseif($req->status === \App\Enums\MarketerSampleRequestStatus::Dispatched) bg-blue-100 text-blue-700
                                    @elseif($req->status === \App\Enums\MarketerSampleRequestStatus::Received) bg-purple-100 text-purple-700
                                    @else bg-red-100 text-red-600
                                    @endif">
                                    {{ ucfirst($req->status->value) }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $req->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-600">
                                {{ __('partner.marketer_samples.marketer_label') }} <strong class="text-gray-800">{{ $req->marketer?->name ?? '—' }}</strong>
                                &nbsp;·&nbsp;
                                {{ __('partner.marketer_samples.campaign_label') }} <strong class="text-gray-800">{{ $req->campaign?->name ?? '—' }}</strong>
                            </p>
                            @if($req->notes)
                                <p class="text-xs text-gray-400 mt-1 italic">{{ $req->notes }}</p>
                            @endif

                            {{-- Combined summary --}}
                            @php
                                $totalQty   = $req->items->sum('quantity');
                                $totalCost  = $req->items->sum('sample_cost');
                                $itemCount  = $req->items->count();
                            @endphp
                            <p class="text-sm font-medium text-gray-800 mt-2">
                                {{ __('partner.marketer_samples.sample_request_number', ['id' => $req->id, 'qty' => $totalQty, 'count' => $itemCount]) }}
                                @if($totalCost > 0)
                                    &nbsp;·&nbsp; {{ __('partner.marketer_samples.total_cost_label', ['amount' => number_format($totalCost / 100, 2), 'currency' => config('app.currency', 'SAR')]) }}
                                @endif
                            </p>

                            {{-- Items flat list, sorted by listing for picking efficiency --}}
                            @if($req->items->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($req->items->sortBy('vendor_listing_id') as $item)
                                        <span class="text-xs bg-gray-100 text-gray-600 rounded-lg px-2 py-1">
                                            {{ $item->vendorListing?->product?->name_en ?? '—' }}
                                            × {{ $item->quantity }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="shrink-0 flex flex-col gap-2">
                            @if($req->status === \App\Enums\MarketerSampleRequestStatus::Requested)
                                <button
                                    class="btn-approve text-xs bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded-lg transition"
                                    data-id="{{ $req->id }}"
                                    data-url="{{ route('partner.marketer-samples.approve', $req) }}">
                                    {{ __('partner.marketer_samples.approve') }}
                                </button>
                                <button
                                    class="btn-reject text-xs bg-red-600 hover:bg-red-700 text-white px-4 py-1.5 rounded-lg transition"
                                    data-id="{{ $req->id }}"
                                    data-url="{{ route('partner.marketer-samples.reject', $req) }}">
                                    {{ __('partner.marketer_samples.reject') }}
                                </button>
                            @elseif($req->status === \App\Enums\MarketerSampleRequestStatus::Approved)
                                <button
                                    class="btn-reject text-xs border border-red-300 text-red-600 hover:bg-red-50 px-4 py-1.5 rounded-lg transition"
                                    data-id="{{ $req->id }}"
                                    data-url="{{ route('partner.marketer-samples.reject', $req) }}">
                                    {{ __('partner.marketer_samples.cancel_approval') }}
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $requests->links() }}</div>
    @endif

</div>

@push('scripts')
<script>
window.PARTNER_TRANSLATIONS = Object.assign(window.PARTNER_TRANSLATIONS || {}, {
    genericError: @json(__('partner.marketer_samples.generic_error')),
    connectionError: @json(__('partner.marketer_samples.connection_error')),
    confirmReject: @json(__('partner.marketer_samples.confirm_reject')),
});

document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = '{{ csrf_token() }}';

    async function postAction(url, buttonEl, successStatus) {
        buttonEl.disabled = true;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                const card = buttonEl.closest('[data-id]') ?? buttonEl.closest('.space-y-3 > div');
                // Reload page to reflect status change
                location.reload();
            } else {
                alert(data.message ?? @json(__('partner.marketer_samples.generic_error')));
                buttonEl.disabled = false;
            }
        } catch (e) {
            alert(@json(__('partner.marketer_samples.connection_error')));
            buttonEl.disabled = false;
        }
    }

    document.querySelectorAll('.btn-approve').forEach(btn => {
        btn.addEventListener('click', () => postAction(btn.dataset.url, btn, 'approved'));
    });

    document.querySelectorAll('.btn-reject').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm(@json(__('partner.marketer_samples.confirm_reject')))) {
                postAction(btn.dataset.url, btn, 'rejected');
            }
        });
    });
});
</script>
@endpush

@endsection
