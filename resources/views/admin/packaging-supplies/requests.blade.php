@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/select2.js', 'resources/js/components/flatpickr.js'])
@endpush

@section('title', __('admin.packaging_supplies.requests_title'))

@section('content')
<div class="p-6 space-y-5">

    <div class="mb-2 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.packaging_supplies.requests_title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.packaging_supplies.requests_subtitle') }}</p>
        </div>
        <a href="{{ route('admin.packaging-supplies.index') }}" class="btn btn-secondary">{{ __('admin.packaging_supplies.catalog') }}</a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <x-stat-card title="{{ __('admin.packaging_supplies.pending') }}"   :value="number_format($stats['pending'])"   iconBg="bg-yellow-100 text-yellow-600" />
        <x-stat-card title="{{ __('admin.packaging_supplies.approved') }}"  :value="number_format($stats['approved'])"  iconBg="bg-blue-100 text-blue-600" />
        <x-stat-card title="{{ __('admin.packaging_supplies.shipped') }}"   :value="number_format($stats['shipped'])"   iconBg="bg-indigo-100 text-indigo-600" />
        <x-stat-card title="{{ __('admin.packaging_supplies.delivered') }}" :value="number_format($stats['delivered'])" iconBg="bg-green-100 text-green-600" />
        <x-stat-card title="{{ __('admin.packaging_supplies.rejected') }}"  :value="number_format($stats['rejected'])"  iconBg="bg-red-100 text-red-600" />
    </div>

    {{-- Filter bar --}}
    <x-card>
        <form id="requests-filter-form" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.status') }}</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.packaging_supplies.all_statuses') }}</option>
                    @foreach(\App\Enums\PackagingSupplyRequestStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.packaging_supplies.vendor') }}</label>
                <select id="filter-vendor" class="form-input w-full text-sm" data-select2-init>
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->store_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.packaging_supplies.date_from') }}</label>
                <input type="text" id="filter-date-from" data-flatpickr class="form-input w-full text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.packaging_supplies.date_to') }}</label>
                <input type="text" id="filter-date-to" data-flatpickr class="form-input w-full text-sm">
            </div>
            <div class="flex items-end">
                <button type="button" id="clear-filters" class="btn btn-ghost btn-sm w-full sm:w-auto">{{ __('common.reset') }}</button>
            </div>
        </form>
    </x-card>

    <x-card>
        <div class="overflow-x-auto">
            <table id="requests-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-start">
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.request_number') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.vendor') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('common.status') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.total') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.delivery_fee') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.currency') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.date') }}</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

</div>

@push('scripts')
<script>
(function () {
    function getFilters() {
        return {
            status: document.getElementById('filter-status').value,
            vendor_id: document.getElementById('filter-vendor').value,
            date_from: document.getElementById('filter-date-from').value,
            date_to: document.getElementById('filter-date-to').value,
        };
    }

    let dtInstance = null;

    function initTable() {
        dtInstance = window.initDataTable
            ? window.initDataTable('requests-table', {
                url: '{{ route("admin.packaging-supplies.requests.datatable") }}',
                columns: [
                    { data: 'request_number', orderable: true, responsivePriority: 1 },
                    { data: 'vendor', orderable: false, responsivePriority: 2 },
                    { data: 'status', orderable: true, responsivePriority: 3 },
                    { data: 'total_cost', orderable: true, responsivePriority: 4 },
                    { data: 'delivery_fee', orderable: false, responsivePriority: 6 },
                    { data: 'currency', orderable: false, responsivePriority: 7 },
                    { data: 'date', orderable: true, responsivePriority: 5 },
                    { data: 'actions', orderable: false, responsivePriority: 1 },
                ],
                order: [[6, 'desc']],
                pageLength: 25,
                extraData: getFilters,
            })
            : window.$('#requests-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("admin.packaging-supplies.requests.datatable") }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                    data: function (d) { Object.assign(d, getFilters()); },
                },
                columns: [
                    { data: 'request_number' }, { data: 'vendor' }, { data: 'status' },
                    { data: 'total_cost' }, { data: 'delivery_fee' }, { data: 'currency' },
                    { data: 'date' }, { data: 'actions', className: 'text-end' },
                ],
                order: [[6, 'desc']],
                pageLength: 25,
                responsive: true,
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTable);
    } else {
        initTable();
    }

    ['filter-status', 'filter-vendor', 'filter-date-from', 'filter-date-to'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.());
    });
    document.getElementById('clear-filters')?.addEventListener('click', () => {
        ['filter-status', 'filter-vendor', 'filter-date-from', 'filter-date-to'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (el._flatpickr) el._flatpickr.clear();
                el.value = '';
                if (window.$ && window.$(el).data('select2')) window.$(el).val('').trigger('change');
            }
        });
        dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.();
    });
})();
</script>
@endpush
@endsection
