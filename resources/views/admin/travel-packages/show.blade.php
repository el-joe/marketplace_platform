@extends('layouts.admin')

@section('title', $travelPackage->title_en)

@section('content')
<div class="p-6 space-y-6 max-w-5xl">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $travelPackage->title_en }}</h1>
            <p class="text-sm text-gray-400">{{ $travelPackage->title_ar }}</p>
            <p class="text-sm text-gray-500 mt-1">Agency: <span class="font-medium text-gray-700">{{ $travelPackage->agency?->name }}</span></p>
        </div>
        <div class="flex gap-2">
            @if($travelPackage->status === 'pending_review')
            <button onclick="approvePackage()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">Approve & Publish</button>
            <button onclick="rejectPackage()" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium">Return to Agency</button>
            @endif
            @if($travelPackage->status === 'active')
            <button onclick="expirePackage()" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium">Mark Expired</button>
            @endif
            <a href="{{ route('admin.travel.packages.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">← Back</a>
        </div>
    </div>

    {{-- Status --}}
    @php
    $statusColors = ['draft'=>'bg-gray-100 text-gray-600','pending_review'=>'bg-amber-100 text-amber-700','active'=>'bg-emerald-100 text-emerald-700','sold_out'=>'bg-purple-100 text-purple-700','expired'=>'bg-gray-100 text-gray-500'];
    @endphp
    <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$travelPackage->status] ?? '' }}">
        {{ ucfirst(str_replace('_', ' ', $travelPackage->status)) }}
    </span>

    {{-- Details --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Trip Details</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Destination</dt><dd class="text-gray-900">{{ $travelPackage->destination_country }}{{ $travelPackage->destination_city ? ', '.$travelPackage->destination_city : '' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Departure</dt><dd class="text-gray-900">{{ $travelPackage->departure_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Return</dt><dd class="text-gray-900">{{ $travelPackage->return_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Duration</dt><dd class="text-gray-900">{{ $travelPackage->duration_days }}D / {{ $travelPackage->duration_nights }}N</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Price</dt><dd class="text-gray-900 font-semibold">{{ $travelPackage->priceFormatted() }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Seats</dt><dd class="text-gray-900">{{ $travelPackage->seats_booked }} / {{ $travelPackage->available_seats ?? '∞' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Inclusions</h3>
            @if($travelPackage->inclusions)
            <ul class="space-y-1 text-sm text-gray-700">
                @foreach($travelPackage->inclusions as $item)
                <li class="flex items-center gap-2"><span class="text-emerald-500">✓</span> {{ $item }}</li>
                @endforeach
            </ul>
            @else
            <p class="text-sm text-gray-400">No inclusions listed.</p>
            @endif
        </div>
    </div>

    {{-- Descriptions --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-3">Description (EN)</h3>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $travelPackage->description_en ?? '—' }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5" dir="rtl">
            <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-3">Description (AR)</h3>
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $travelPackage->description_ar ?? '—' }}</p>
        </div>
    </div>

    {{-- Media Gallery --}}
    @if($travelPackage->media->count())
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-4">Media ({{ $travelPackage->media->count() }})</h3>
        <div class="grid grid-cols-4 gap-3">
            @foreach($travelPackage->media as $m)
            @if($m->media_type === 'image')
            <img src="{{ $m->url() }}" class="rounded-lg h-32 w-full object-cover border border-gray-200">
            @else
            <video src="{{ $m->url() }}" controls class="rounded-lg h-32 w-full object-cover border border-gray-200"></video>
            @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Approval info --}}
    @if($travelPackage->approvedByAdmin)
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-sm text-emerald-700">
        Approved by <strong>{{ $travelPackage->approvedByAdmin->name }}</strong> on {{ $travelPackage->approved_at->format('d M Y H:i') }}
    </div>
    @endif
</div>

@push('scripts')
<script>
const pkgId = '{{ $travelPackage->id }}';

async function approvePackage() {
    if (!confirm('Approve and publish this package?')) return;
    const res = await fetch(`/admin/travel/packages/${pkgId}/approve`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    if (res.ok) location.reload();
    else alert('Error approving package.');
}

async function rejectPackage() {
    const reason = prompt('Reason for returning to agency:');
    if (!reason) return;
    const res = await fetch(`/admin/travel/packages/${pkgId}/reject`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ reason }),
    });
    if (res.ok) location.reload();
    else alert('Error rejecting package.');
}

async function expirePackage() {
    if (!confirm('Mark this package as expired?')) return;
    const res = await fetch(`/admin/travel/packages/${pkgId}/expire`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    if (res.ok) location.reload();
    else alert('Error expiring package.');
}
</script>
@endpush
@endsection
