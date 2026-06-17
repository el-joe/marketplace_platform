@extends('layouts.admin')

@section('title', $travelAgency->name)

@section('content')
<div class="p-6 space-y-6 max-w-5xl">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div class="flex items-center gap-4">
            @if($travelAgency->logoUrl())
            <img src="{{ $travelAgency->logoUrl() }}" class="w-16 h-16 rounded-xl object-cover border border-gray-200">
            @else
            <div class="w-16 h-16 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold">
                {{ mb_substr($travelAgency->name, 0, 1) }}
            </div>
            @endif
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $travelAgency->name }}</h1>
                <p class="text-sm text-gray-500">{{ $travelAgency->email }}</p>
            </div>
        </div>

        <div class="flex gap-2">
            @if($travelAgency->status === 'pending')
            <button onclick="approveAgency()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">Approve</button>
            <button onclick="rejectAgency()" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium">Reject</button>
            @elseif($travelAgency->status === 'active')
            <button onclick="suspendAgency()" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium">Suspend</button>
            @elseif($travelAgency->status === 'suspended')
            <button onclick="reactivateAgency()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium">Reactivate</button>
            @endif
            <a href="{{ route('admin.travel.agencies.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">← Back</a>
        </div>
    </div>

    {{-- Status Badge --}}
    @php
    $statusColors = ['pending' => 'bg-amber-100 text-amber-700', 'active' => 'bg-emerald-100 text-emerald-700', 'suspended' => 'bg-red-100 text-red-700'];
    @endphp
    <span class="inline-block px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$travelAgency->status] ?? '' }}">
        {{ ucfirst($travelAgency->status) }}
    </span>

    {{-- Details Grid --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Agency Info</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Phone</dt><dd class="text-gray-900">{{ $travelAgency->phone ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">License No.</dt><dd class="text-gray-900">{{ $travelAgency->license_number ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Country</dt><dd class="text-gray-900">{{ $travelAgency->country?->name_en }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Registered</dt><dd class="text-gray-900">{{ $travelAgency->created_at->format('d M Y') }}</dd></div>
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Approval</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Approved By</dt><dd class="text-gray-900">{{ $travelAgency->approvedByAdmin?->name ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Approved At</dt><dd class="text-gray-900">{{ $travelAgency->approved_at?->format('d M Y H:i') ?? '—' }}</dd></div>
            </dl>
        </div>
    </div>

    {{-- Packages --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Travel Packages ({{ $travelAgency->packages->count() }})</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Title</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Destination</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Departure</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Price</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($travelAgency->packages as $pkg)
                @php
                $pkgColors = ['draft'=>'bg-gray-100 text-gray-600','pending_review'=>'bg-amber-100 text-amber-700','active'=>'bg-emerald-100 text-emerald-700','sold_out'=>'bg-purple-100 text-purple-700','expired'=>'bg-gray-100 text-gray-500'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $pkg->title_en }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->destination_country }}{{ $pkg->destination_city ? ', '.$pkg->destination_city : '' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->departure_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $pkg->priceFormatted() }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $pkgColors[$pkg->status] ?? '' }}">
                            {{ ucfirst(str_replace('_',' ',$pkg->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.travel.packages.show', $pkg) }}" class="text-primary-600 text-xs hover:underline">Review</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">No packages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
const agencyId = '{{ $travelAgency->id }}';

async function approveAgency() {
    if (!confirm('Approve this travel agency?')) return;
    const res = await fetch(`/admin/travel/agencies/${agencyId}/approve`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    if (res.ok) location.reload();
    else alert('Error approving agency.');
}

async function rejectAgency() {
    const reason = prompt('Rejection reason:');
    if (!reason) return;
    const res = await fetch(`/admin/travel/agencies/${agencyId}/reject`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ reason }),
    });
    if (res.ok) location.href = '{{ route('admin.travel.agencies.index') }}';
    else alert('Error rejecting agency.');
}

async function suspendAgency() {
    const reason = prompt('Suspension reason:');
    if (!reason) return;
    const res = await fetch(`/admin/travel/agencies/${agencyId}/suspend`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ reason }),
    });
    if (res.ok) location.reload();
    else alert('Error suspending agency.');
}

async function reactivateAgency() {
    if (!confirm('Reactivate this agency?')) return;
    const res = await fetch(`/admin/travel/agencies/${agencyId}/reactivate`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    if (res.ok) location.reload();
    else alert('Error reactivating agency.');
}
</script>
@endpush
@endsection
