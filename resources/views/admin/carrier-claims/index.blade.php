@extends('layouts.admin')

@section('title', 'Carrier Claims')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Carrier Claims</h1>
            <p class="text-sm text-gray-500 mt-0.5">Lost, damaged, delayed, and disputed shipment claims.</p>
        </div>
        {{-- Admin submits on behalf if needed --}}
    </div>

    {{-- ─── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <x-stat-card title="Submitted"    :value="number_format($stats['submitted'])"    iconBg="bg-yellow-100 text-yellow-600" />
        <x-stat-card title="Under Review" :value="number_format($stats['under_review'])" iconBg="bg-blue-100 text-blue-600" />
        <x-stat-card title="Approved"     :value="number_format($stats['approved'])"     iconBg="bg-green-100 text-green-600" />
        <x-stat-card title="Compensated"  :value="number_format($stats['compensated'])"  iconBg="bg-emerald-100 text-emerald-600" />
        <x-stat-card title="Rejected"     :value="number_format($stats['rejected'])"     iconBg="bg-red-100 text-red-600" />
    </div>

    {{-- ─── Filters ─────────────────────────────────────────────────────────── --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="status" class="input-sm">
            <option value="">All statuses</option>
            @foreach(['submitted','under_review','approved','compensated','rejected'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ Str::title(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>

        <select name="shipping_company_id" class="input-sm">
            <option value="">All carriers</option>
            @foreach($companies as $c)
                <option value="{{ $c->id }}" @selected(request('shipping_company_id') === $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>

        <select name="claim_type" class="input-sm">
            <option value="">All types</option>
            @foreach(['lost','damaged','delayed','wrong_item','other'] as $t)
                <option value="{{ $t }}" @selected(request('claim_type') === $t)>{{ Str::title(str_replace('_',' ',$t)) }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn-sm btn-primary">Filter</button>
        <a href="{{ route('admin.carrier-claims.index') }}" class="btn-sm btn-secondary">Reset</a>
    </form>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">Claim #</th>
                    <th class="th">Type</th>
                    <th class="th">Carrier</th>
                    <th class="th">Claimed</th>
                    <th class="th">Status</th>
                    <th class="th">Submitted</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($claims as $claim)
                    <tr class="hover:bg-gray-50">
                        <td class="td font-mono text-xs">{{ $claim->claim_number }}</td>
                        <td class="td">{{ Str::title(str_replace('_',' ',$claim->claim_type)) }}</td>
                        <td class="td text-gray-700">{{ $claim->shippingCompany?->name ?? '—' }}</td>
                        <td class="td font-medium">{{ number_format($claim->claimed_amount_cents / 100, 2) }}</td>
                        <td class="td">
                            <span class="badge {{ $claim->statusBadgeClass() }}">
                                {{ Str::title(str_replace('_',' ',$claim->status)) }}
                            </span>
                        </td>
                        <td class="td text-gray-500 text-xs">{{ $claim->created_at->format('d M Y') }}</td>
                        <td class="td text-right">
                            <a href="{{ route('admin.carrier-claims.show', $claim) }}"
                               class="text-primary-600 hover:underline text-xs font-medium">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="td text-center text-gray-400 py-10">No claims found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $claims->links() }}
    </div>

@endsection
