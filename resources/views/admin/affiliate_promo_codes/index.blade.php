@extends('layouts.admin')

@push('scripts')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Affiliate Promo Codes')

@section('content')

<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Affiliate Promo Codes</h1>
        <p class="text-sm text-gray-500 mt-0.5">Create and manage promo codes issued to affiliate marketers.</p>
    </div>
    <button type="button" id="create-promo-btn" class="btn btn-primary btn-sm">Create Promo Code</button>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Total Codes', 'value' => number_format($stats['total']), 'color' => 'gray'],
        ['label' => 'Active', 'value' => number_format($stats['active']), 'color' => 'success'],
        ['label' => 'Total Uses', 'value' => number_format($stats['total_uses']), 'color' => 'primary'],
        ['label' => 'Total Revenue', 'value' => number_format($stats['total_revenue'] / 100, 2), 'color' => 'primary'],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

<x-card class="mb-5">
    <form id="filter-form" class="flex flex-wrap gap-3 items-end">
        <div class="w-56">
            <label class="block text-xs font-medium text-gray-600 mb-1">Affiliate</label>
            <select id="filter-marketer" class="form-input w-full text-sm">
                <option value="">All Affiliates</option>
                @foreach($affiliates as $affiliate)
                    <option value="{{ $affiliate->id }}">{{ $affiliate->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-36">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select id="filter-active" class="form-input w-full text-sm">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
    </form>
</x-card>

<x-card>
    <div class="overflow-x-auto">
        <table id="promo-codes-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Marketer</th>
                    <th>Discount</th>
                    <th>Uses</th>
                    <th>Revenue Generated</th>
                    <th>Commission Earned</th>
                    <th>Status</th>
                    <th>Valid Until</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</x-card>

{{-- ─── Create Promo Code Modal ─────────────────────────────────────────────── --}}
<div id="create-promo-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-gray-900">Create Promo Code</h3>
            <button type="button" data-modal-close class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
        </div>
        <form id="create-promo-form">
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="form-label">Affiliate <span class="text-red-500">*</span></label>
                    <select name="marketer_id" class="form-input w-full" required>
                        <option value="">Select affiliate…</option>
                        @foreach($affiliates as $affiliate)
                            <option value="{{ $affiliate->id }}">{{ $affiliate->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-input w-full" placeholder="Leave blank to auto-generate (AFF-XX-XXXXXX)">
                </div>
                <div>
                    <label class="form-label">Discount Type <span class="text-red-500">*</span></label>
                    <select name="discount_type" class="form-input w-full" required>
                        <option value="percentage">Percentage</option>
                        <option value="fixed_amount">Fixed Amount</option>
                        <option value="free_shipping">Free Shipping</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Discount Value <span class="text-red-500">*</span></label>
                    <input type="number" name="discount_value" class="form-input w-full" step="0.01" min="0" required>
                </div>
                <div>
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" class="form-input w-full" maxlength="3" placeholder="e.g. SAR">
                </div>
                <div>
                    <label class="form-label">Max Uses</label>
                    <input type="number" name="max_uses" class="form-input w-full" min="1" placeholder="Unlimited">
                </div>
                <div>
                    <label class="form-label">Min Order Amount</label>
                    <input type="number" name="min_order_amount" class="form-input w-full" min="0" placeholder="No minimum">
                </div>
                <div>
                    <label class="form-label">Valid From <span class="text-red-500">*</span></label>
                    <input type="date" name="valid_from" class="form-input w-full" required>
                </div>
                <div class="col-span-2">
                    <label class="form-label">Valid Until <span class="text-red-500">*</span></label>
                    <input type="date" name="valid_until" class="form-input w-full" required>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" id="create-promo-submit" class="btn btn-primary btn-sm">Create Promo Code</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
$(function () {
    const tok = '{{ csrf_token() }}';

    const table = $('#promo-codes-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.affiliate-promo-codes.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data: function (d) {
                d.filter_marketer = $('#filter-marketer').val();
                d.filter_active = $('#filter-active').val();
            },
        },
        columns: [{}, {}, {}, {}, {}, {}, {}, {}, { orderable: false, searchable: false }],
        order: [[7, 'desc']],
        pageLength: 25,
    });

    $('#filter-marketer, #filter-active').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', () => {
        $('#filter-marketer').val('');
        $('#filter-active').val('');
        table.ajax.reload();
    });

    // ── Create modal ────────────────────────────────────────────────────────
    $('#create-promo-btn').on('click', () => $('#create-promo-modal').removeClass('hidden'));
    $('#create-promo-modal [data-modal-close]').on('click', () => $('#create-promo-modal').addClass('hidden'));

    $('#create-promo-form').on('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());

        $.ajax({
            url: '{{ route('admin.affiliate-promo-codes.store') }}',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': tok },
            data,
        })
            .done(r => {
                window.Toast.success(r.message);
                $('#create-promo-modal').addClass('hidden');
                this.reset();
                table.ajax.reload();
            })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Something went wrong.'));
    });

    // ── Row actions (delegated) ─────────────────────────────────────────────
    $(document).on('click', '.btn-toggle-promo', function () {
        const id = $(this).data('id');
        $.post('{{ url('affiliate-promo-codes') }}/' + id + '/toggle', { _token: tok })
            .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Something went wrong.'));
    });

    $(document).on('click', '.btn-disable-promo', function () {
        const id = $(this).data('id');
        window.confirmDialog({ title: 'Disable this promo code?', onConfirm: () => {
            $.ajax({
                url: '{{ url('affiliate-promo-codes') }}/' + id,
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': tok },
            })
                .done(r => { window.Toast.success(r.message); table.ajax.reload(); })
                .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Something went wrong.'));
        }});
    });
});
</script>
@endpush
