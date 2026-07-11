@extends('layouts.admin')

@section('title', __('admin.packaging_supplies.requests_title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.packaging_supplies.requests_title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.packaging_supplies.requests_subtitle') }}</p>
        </div>
        <a href="{{ route('admin.packaging-supplies.index') }}" class="btn btn-secondary">{{ __('admin.packaging_supplies.catalog') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    {{-- ─── Stats ───────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <x-stat-card title="{{ __('admin.packaging_supplies.pending') }}"   :value="number_format($stats['pending'])"   iconBg="bg-yellow-100 text-yellow-600" />
        <x-stat-card title="{{ __('admin.packaging_supplies.approved') }}"  :value="number_format($stats['approved'])"  iconBg="bg-blue-100 text-blue-600" />
        <x-stat-card title="{{ __('admin.packaging_supplies.shipped') }}"   :value="number_format($stats['shipped'])"   iconBg="bg-indigo-100 text-indigo-600" />
        <x-stat-card title="{{ __('admin.packaging_supplies.delivered') }}" :value="number_format($stats['delivered'])" iconBg="bg-green-100 text-green-600" />
        <x-stat-card title="{{ __('admin.packaging_supplies.rejected') }}"  :value="number_format($stats['rejected'])"  iconBg="bg-red-100 text-red-600" />
    </div>

    {{-- ─── Filter ──────────────────────────────────────────────────────────── --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <select name="status" class="input-sm">
            <option value="">{{ __('admin.packaging_supplies.all_statuses') }}</option>
            @foreach(\App\Enums\PackagingSupplyRequestStatus::cases() as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-sm btn-primary">{{ __('admin.packaging_supplies.filter') }}</button>
        <a href="{{ route('admin.packaging-supplies.requests') }}" class="btn-sm btn-secondary">{{ __('admin.packaging_supplies.reset') }}</a>
    </form>

    {{-- ─── Table ───────────────────────────────────────────────────────────── --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="th">{{ __('admin.packaging_supplies.request_number') }}</th>
                        <th class="th">{{ __('admin.packaging_supplies.vendor') }}</th>
                        <th class="th">{{ __('admin.packaging_supplies.warehouse') }}</th>
                        <th class="th">{{ __('admin.packaging_supplies.total') }}</th>
                        <th class="th">{{ __('admin.packaging_supplies.status') }}</th>
                        <th class="th">{{ __('admin.packaging_supplies.date') }}</th>
                        <th class="th"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($supplyRequests as $req)
                        <tr class="hover:bg-gray-50">
                            <td class="td font-mono text-xs">{{ $req->request_number }}</td>
                            <td class="td font-medium text-gray-900">{{ $req->vendor->store_name }}</td>
                            <td class="td text-gray-500">{{ $req->warehouse?->name ?? '—' }}</td>
                            <td class="td">{{ $req->total_cost_formatted }}</td>
                            <td class="td">
                                <span class="badge {{ $req->statusBadgeClass() }}">{{ $req->status->label() }}</span>
                            </td>
                            <td class="td text-gray-500 text-xs">{{ $req->created_at->format('d M Y') }}</td>
                            <td class="td text-end">
                                <a href="{{ route('admin.packaging-supplies.show-request', $req) }}"
                                   class="text-primary-600 hover:underline text-xs font-medium">{{ __('admin.packaging_supplies.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="td text-center text-gray-400 py-10">{{ __('admin.packaging_supplies.no_requests_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $supplyRequests->links() }}</div>

@endsection
