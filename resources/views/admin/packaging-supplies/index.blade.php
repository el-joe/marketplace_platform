@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', __('admin.packaging_supplies.title'))

@section('content')
<div class="p-6 space-y-5">

    <div class="mb-2 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.packaging_supplies.title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.packaging_supplies.subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.packaging-supplies.requests') }}" class="btn btn-secondary">{{ __('admin.packaging_supplies.requests_queue') }}</a>
            <a href="{{ route('admin.packaging-supplies.create') }}" class="btn btn-primary">{{ __('admin.packaging_supplies.add_supply') }}</a>
        </div>
    </div>

    {{-- Filter bar --}}
    <x-card>
        <form id="supplies-filter-form" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:items-end">
            <div class="sm:col-span-2 lg:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.search') }}</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="{{ __('admin.packaging_supplies.name') }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.packaging_supplies.type') }}</label>
                <select id="filter-type" class="form-input w-full text-sm">
                    <option value="">{{ __('admin.packaging_supplies.select_type') }}</option>
                    @foreach(\App\Enums\PackagingSupplyType::cases() as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('common.status') }}</label>
                <select id="filter-active" class="form-input w-full text-sm">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="1">{{ __('admin.packaging_supplies.active') }}</option>
                    <option value="0">{{ __('admin.packaging_supplies.inactive') }}</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" id="clear-filters" class="btn btn-ghost btn-sm w-full sm:w-auto">{{ __('common.reset') }}</button>
            </div>
        </form>
    </x-card>

    <x-card>
        <div class="overflow-x-auto">
            <table id="supplies-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-start">
                        <th class="py-2 pr-3 text-xs font-medium text-gray-500 uppercase w-12"></th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.name') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.type') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.size') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.unit_cost') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.stock') }}</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">{{ __('admin.packaging_supplies.status') }}</th>
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
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function getFilters() {
        return {
            search: { value: document.getElementById('search-input').value },
            type: document.getElementById('filter-type').value,
            is_active: document.getElementById('filter-active').value,
        };
    }

    let dtInstance = null;

    function initTable() {
        if (typeof window.initDataTable !== 'function') {
            dtInstance = window.$('#supplies-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("admin.packaging-supplies.datatable") }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    data: function (d) {
                        const f = getFilters();
                        d.type = f.type;
                        d.is_active = f.is_active;
                        if (f.search.value) d.search = f.search;
                    },
                },
                columns: [
                    { data: 'image', orderable: false, responsivePriority: 4 },
                    { data: 'name', orderable: true, responsivePriority: 1 },
                    { data: 'type', orderable: true, responsivePriority: 3 },
                    { data: 'size', orderable: true, responsivePriority: 6 },
                    { data: 'unit_cost', orderable: true, responsivePriority: 5 },
                    { data: 'stock', orderable: true, responsivePriority: 7 },
                    { data: 'status', orderable: true, responsivePriority: 2 },
                    { data: 'actions', orderable: false, className: 'text-end', responsivePriority: 1 },
                ],
                order: [[1, 'asc']],
                pageLength: 25,
                responsive: true,
            });
        } else {
            dtInstance = window.initDataTable('supplies-table', {
                url: '{{ route("admin.packaging-supplies.datatable") }}',
                columns: [
                    { data: 'image', orderable: false, responsivePriority: 4 },
                    { data: 'name', orderable: true, responsivePriority: 1 },
                    { data: 'type', orderable: true, responsivePriority: 3 },
                    { data: 'size', orderable: true, responsivePriority: 6 },
                    { data: 'unit_cost', orderable: true, responsivePriority: 5 },
                    { data: 'stock', orderable: true, responsivePriority: 7 },
                    { data: 'status', orderable: true, responsivePriority: 2 },
                    { data: 'actions', orderable: false, responsivePriority: 1 },
                ],
                order: [[1, 'asc']],
                pageLength: 25,
                extraData: getFilters,
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTable);
    } else {
        initTable();
    }

    ['filter-type', 'filter-active'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.());
    });
    document.getElementById('search-input')?.addEventListener('input', function () {
        clearTimeout(this._t);
        this._t = setTimeout(() => dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.(), 400);
    });
    document.getElementById('clear-filters')?.addEventListener('click', () => {
        ['filter-type', 'filter-active', 'search-input'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.();
    });

    document.getElementById('supplies-table').addEventListener('submit', async (e) => {
        const form = e.target.closest('.btn-delete-form');
        if (!form) return;
        e.preventDefault();
        if (!confirm('{{ __('admin.packaging_supplies.delete_supply_confirm') }}')) return;
        const res = await fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: new FormData(form),
        });
        if (res.ok || res.redirected) {
            dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.();
        }
    });
})();
</script>
@endpush
@endsection
