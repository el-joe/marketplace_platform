@extends('layouts.admin')

@section('title', $travelPackage->title_en)

@section('content')
<div class="p-6 space-y-6 max-w-5xl">

    {{-- ─── Header ──────────────────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.travel.packages.index') }}" class="text-xs text-gray-400 hover:text-gray-600 mb-1 inline-block">← Travel Packages</a>
            <h1 class="text-2xl font-bold text-gray-900">{{ $travelPackage->title_en }}</h1>
            @if($travelPackage->title_ar)
                <p class="text-sm text-gray-400 mt-0.5" dir="rtl">{{ $travelPackage->title_ar }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if($travelPackage->status === 'pending_review')
                <button type="button" class="btn btn-success js-show-approve-btn">Approve &amp; Publish</button>
                <button type="button" class="btn btn-danger js-show-reject-btn">Return to Agency</button>
            @elseif(in_array($travelPackage->status, ['active', 'sold_out']))
                <button type="button" class="btn btn-secondary js-expire-btn"
                    data-url="{{ route('admin.travel.packages.expire', $travelPackage->id) }}">
                    Mark Expired
                </button>
            @endif
        </div>
    </div>

    {{-- ─── Status + rejection note ─────────────────────────────────────────────── --}}
    @php
    $statusColors = [
        'draft'          => 'bg-gray-100 text-gray-600',
        'pending_review' => 'bg-amber-100 text-amber-700',
        'active'         => 'bg-emerald-100 text-emerald-700',
        'sold_out'       => 'bg-purple-100 text-purple-700',
        'expired'        => 'bg-gray-100 text-gray-500',
    ];
    @endphp
    <div class="flex items-center gap-3">
        <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$travelPackage->status] ?? 'bg-gray-100 text-gray-600' }}">
            {{ ucfirst(str_replace('_', ' ', $travelPackage->status)) }}
        </span>
        @if($travelPackage->approved_at && $travelPackage->approvedByAdmin)
            <span class="text-xs text-gray-400">
                Approved by {{ $travelPackage->approvedByAdmin->name }} · {{ $travelPackage->approved_at->format('d M Y H:i') }}
            </span>
        @endif
    </div>

    @if($travelPackage->rejection_reason)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
        <p class="font-medium mb-1">Previously returned to agency with reason:</p>
        <p class="whitespace-pre-wrap">{{ $travelPackage->rejection_reason }}</p>
    </div>
    @endif

    {{-- ─── Main grid ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Trip Details --}}
        <x-card>
            <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Trip Details</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Destination</dt>
                    <dd class="text-gray-900 text-right">
                        @if($travelPackage->destinationCountry)
                            {{ $travelPackage->destinationCountry->flag_emoji }} {{ $travelPackage->destinationCountry->name_en }}
                            @if($travelPackage->destinationCity)
                                <span class="text-gray-400">, {{ $travelPackage->destinationCity->name_en }}</span>
                            @endif
                        @else
                            {{ $travelPackage->destination_country }}{{ $travelPackage->destination_city ? ', ' . $travelPackage->destination_city : '' }}
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">Departure</dt><dd class="text-gray-900">{{ $travelPackage->departure_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Return</dt><dd class="text-gray-900">{{ $travelPackage->return_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Duration</dt><dd class="text-gray-900">{{ $travelPackage->duration_days }}d / {{ $travelPackage->duration_nights }}n</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Price</dt><dd class="text-gray-900 font-semibold">{{ $travelPackage->priceFormatted() }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Seats (booked / available)</dt>
                    <dd class="text-gray-900">{{ $travelPackage->seats_booked }} / {{ $travelPackage->available_seats ?? '∞' }}</dd>
                </div>
                @if($fillPct !== null)
                <div>
                    <div class="flex justify-between text-xs text-gray-400 mb-0.5"><span>Fill rate</span><span>{{ $fillPct }}%</span></div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="{{ $fillPct >= 90 ? 'bg-red-500' : ($fillPct >= 70 ? 'bg-yellow-500' : 'bg-green-500') }} h-1.5 rounded-full" style="width:{{ $fillPct }}%"></div>
                    </div>
                </div>
                @endif
            </dl>
        </x-card>

        {{-- Agency Card --}}
        <x-card>
            <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Agency</h3>
            @php $agency = $travelPackage->agency; @endphp
            @if($agency)
            <div class="flex items-center gap-3 mb-3">
                @if($agency->logo_path)
                    <img src="{{ Storage::url($agency->logo_path) }}" class="w-12 h-12 rounded-lg object-contain border border-gray-200" alt="">
                @else
                    <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 text-xs">Logo</div>
                @endif
                <div>
                    <p class="font-semibold text-gray-900">{{ $agency->name }}</p>
                    <p class="text-xs text-gray-500">{{ $agency->email }}</p>
                    @if($agency->phone)<p class="text-xs text-gray-500">{{ $agency->phone }}</p>@endif
                </div>
            </div>
            <dl class="space-y-1 text-sm">
                @if($agency->license_number)
                <div class="flex justify-between"><dt class="text-gray-500">License</dt><dd class="text-gray-900 font-mono text-xs">{{ $agency->license_number }}</dd></div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $agency->status === 'active' ? 'bg-success-100 text-success-700' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($agency->status) }}</span></dd>
                </div>
            </dl>
            <div class="mt-3">
                <a href="{{ route('admin.travel.agencies.show', $agency->id) }}" class="btn btn-secondary btn-sm">View Agency</a>
            </div>
            @else
                <p class="text-sm text-gray-400">Agency not found.</p>
            @endif
        </x-card>

    </div>

    {{-- ─── Booking Stats ────────────────────────────────────────────────────────── --}}
    <x-card>
        <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Booking Stats (this package)</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $bookingStats['total'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Total Bookings</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-success-600">{{ $bookingStats['confirmed'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Confirmed</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-danger-600">{{ $bookingStats['cancelled'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Cancelled</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-900">
                    {{ $travelPackage->currency }} {{ number_format($bookingStats['revenue_cents'] / 100, 2) }}
                </p>
                <p class="text-xs text-gray-500 mt-0.5">Confirmed Revenue</p>
            </div>
        </div>
    </x-card>

    {{-- ─── Inclusions ───────────────────────────────────────────────────────────── --}}
    @if($travelPackage->inclusions)
    <x-card>
        <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Inclusions</h3>
        <ul class="grid grid-cols-2 gap-1 text-sm text-gray-700">
            @foreach($travelPackage->inclusions as $item)
            <li class="flex items-center gap-2"><span class="text-success-500 shrink-0">✓</span> {{ $item }}</li>
            @endforeach
        </ul>
    </x-card>
    @endif

    {{-- ─── Categories ───────────────────────────────────────────────────────────── --}}
    @if($travelPackage->categories->count())
    <x-card>
        <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Categories</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($travelPackage->categories as $cat)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700">
                {{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name_en }}
            </span>
            @endforeach
        </div>
    </x-card>
    @endif

    {{-- ─── Descriptions ─────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-card>
            <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Description (EN)</h3>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $travelPackage->description_en ?? '—' }}</p>
        </x-card>
        <x-card class="" style="direction:rtl">
            <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">الوصف (AR)</h3>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $travelPackage->description_ar ?? '—' }}</p>
        </x-card>
    </div>

    {{-- ─── Contract File ───────────────────────────────────────────────────────────── --}}
    <x-card>
        <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-3">Package Contract</h3>
        @if($travelPackage->contract_file_path)
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <svg class="w-8 h-8 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $travelPackage->contract_file_original_name }}</p>
                    @if($travelPackage->contract_uploaded_at)
                    <p class="text-xs text-gray-400 mt-0.5">Uploaded {{ $travelPackage->contract_uploaded_at->format('d M Y H:i') }}</p>
                    @endif
                </div>
            </div>
            <a href="{{ route('admin.travel.packages.contract.download', $travelPackage->id) }}"
               class="btn btn-secondary btn-sm shrink-0">Download PDF</a>
        </div>
        @else
        <p class="text-sm text-amber-600 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            No contract file uploaded (grandfathered package).
        </p>
        @endif
    </x-card>

    {{-- ─── Media Gallery ─────────────────────────────────────────────────────────── --}}
    @if($travelPackage->media->count())
    <x-card>
        <h3 class="font-semibold text-gray-700 text-xs uppercase tracking-wide mb-4">Media ({{ $travelPackage->media->count() }} files)</h3>
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
            @foreach($travelPackage->media as $m)
            @if($m->media_type === 'image')
                <a href="/storage/{{ $m->file_path }}" target="_blank">
                    <img src="/storage/{{ $m->file_path }}" class="rounded-lg h-28 w-full object-cover border border-gray-200 hover:opacity-90 transition-opacity" alt="">
                </a>
            @else
                <video src="/storage/{{ $m->file_path }}" controls class="rounded-lg h-28 w-full object-cover border border-gray-200"></video>
            @endif
            @endforeach
        </div>
    </x-card>
    @endif

</div>

{{-- ─── Approve Modal ────────────────────────────────────────────────────────── --}}
<x-modal id="approve-modal" title="Approve Package" size="sm">
    <p class="text-sm text-gray-600">
        Approve <strong>{{ $travelPackage->title_en }}</strong> and make it live for customers?
    </p>
    <div class="flex justify-end gap-3 mt-5">
        <button type="button" class="btn btn-secondary" onclick="$('#approve-modal').modal('close')">Cancel</button>
        <button type="button" id="confirm-approve-btn" class="btn btn-success">Approve &amp; Publish</button>
    </div>
</x-modal>

{{-- ─── Reject Modal ─────────────────────────────────────────────────────────── --}}
<x-modal id="reject-modal" title="Return Package to Agency" size="md">
    <p class="text-sm text-gray-600 mb-3">
        Return <strong>{{ $travelPackage->title_en }}</strong> to draft. The agency will receive the reason and can correct and resubmit.
    </p>
    <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-red-500">*</span></label>
    <textarea id="reject-reason-input" rows="4" class="form-input w-full text-sm"
        placeholder="Describe what needs to be corrected before the agency resubmits…"></textarea>
    <p class="text-xs text-red-500 hidden mt-1" id="reject-reason-error">A reason is required.</p>
    <div class="flex justify-end gap-3 mt-5">
        <button type="button" class="btn btn-secondary" onclick="$('#reject-modal').modal('close')">Cancel</button>
        <button type="button" id="confirm-reject-btn" class="btn btn-danger">Return to Draft</button>
    </div>
</x-modal>

@endsection

@push('scripts')
<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const approveUrl = '{{ route('admin.travel.packages.approve', $travelPackage->id) }}';
    const rejectUrl  = '{{ route('admin.travel.packages.reject', $travelPackage->id) }}';
    const expireUrl  = '{{ route('admin.travel.packages.expire', $travelPackage->id) }}';

    function postJson(url, data) {
        return $.ajax({ url, type: 'POST', data: JSON.stringify(data ?? {}), contentType: 'application/json', headers: { 'X-CSRF-TOKEN': csrfToken } });
    }

    document.querySelector('.js-show-approve-btn')?.addEventListener('click', () => $('#approve-modal').modal('open'));
    document.querySelector('.js-show-reject-btn')?.addEventListener('click', () => $('#reject-modal').modal('open'));

    document.querySelector('.js-expire-btn')?.addEventListener('click', function () {
        if (!confirm('Mark this package as expired?')) return;
        postJson(this.dataset.url).done(() => location.reload()).fail(xhr => alert(xhr.responseJSON?.message ?? 'Failed.'));
    });

    document.getElementById('confirm-approve-btn')?.addEventListener('click', () => {
        postJson(approveUrl).done(() => location.reload()).fail(xhr => {
            $('#approve-modal').modal('close');
            alert(xhr.responseJSON?.message ?? 'Failed to approve.');
        });
    });

    document.getElementById('confirm-reject-btn')?.addEventListener('click', () => {
        const reason = document.getElementById('reject-reason-input').value.trim();
        if (!reason) { document.getElementById('reject-reason-error').classList.remove('hidden'); return; }
        postJson(rejectUrl, { rejection_reason: reason }).done(() => location.reload()).fail(xhr => {
            alert(xhr.responseJSON?.message ?? 'Failed.');
        });
    });
})();
</script>
@endpush
