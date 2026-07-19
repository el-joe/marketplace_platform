@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Influencer Deals')

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Influencer Deals</h1>
        <p class="text-sm text-gray-500 mt-0.5">Propose, review and pay out influencer/celebrity/brand ambassador deals.</p>
    </div>
    <button type="button" id="propose-deal-btn" class="btn btn-primary btn-sm">Propose New Deal</button>
</div>

{{-- ─── Stats Row ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => 'Total Deals',       'value' => $stats['total'],            'color' => 'gray'],
        ['label' => 'Proposed',          'value' => $stats['proposed'],         'color' => 'warning'],
        ['label' => 'Pending Approval',  'value' => $stats['pending_approval'], 'color' => 'primary'],
        ['label' => 'Paid',              'value' => $stats['paid'],             'color' => 'success'],
    ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
        </div>
    @endforeach
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<x-card class="mb-5">
    <form id="filter-form" class="flex flex-wrap gap-3 items-end">
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select id="filter-status" class="form-input w-full text-sm">
                <option value="">All statuses</option>
                <option value="proposed">Proposed</option>
                <option value="negotiating">Negotiating</option>
                <option value="accepted">Accepted</option>
                <option value="in_progress">In Progress</option>
                <option value="content_submitted">Content Submitted</option>
                <option value="approved">Approved</option>
                <option value="paid">Paid</option>
                <option value="cancelled">Cancelled</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="w-56">
            <label class="block text-xs font-medium text-gray-600 mb-1">Marketer</label>
            <select id="filter-marketer" class="form-input w-full text-sm">
                <option value="">All marketers</option>
                @foreach($marketers as $marketer)
                    <option value="{{ $marketer->id }}">{{ $marketer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-56">
            <label class="block text-xs font-medium text-gray-600 mb-1">Vendor</label>
            <select id="filter-vendor" class="form-input w-full text-sm">
                <option value="">All vendors</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->store_name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
    </form>
</x-card>

{{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
<x-card>
    <div class="overflow-x-auto">
        <table id="influencer-deals-table" class="w-full text-sm" style="width:100%">
            <thead>
                <tr>
                    <th>Deal Name</th>
                    <th>Marketer</th>
                    <th>Vendor</th>
                    <th>Type</th>
                    <th>Flat Fee</th>
                    <th>Status</th>
                    <th>Deliverables</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</x-card>

{{-- ─── Propose Deal Modal ─────────────────────────────────────────────────── --}}
<div id="propose-deal-modal" class="modal-backdrop hidden">
    <div class="modal-box max-w-2xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-semibold text-gray-900">Propose New Deal</h3>
            <button type="button" data-modal-close class="text-gray-400 hover:text-gray-600 text-2xl leading-none p-1">&times;</button>
        </div>
        <form id="propose-deal-form">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Marketer <span class="text-red-500">*</span></label>
                    <select name="marketer_id" class="form-input w-full" required>
                        <option value="">Select marketer</option>
                        @foreach($marketers as $marketer)
                            <option value="{{ $marketer->id }}">{{ $marketer->name }} ({{ ucfirst(str_replace('_',' ', $marketer->type?->value ?? $marketer->type)) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-input w-full">
                        <option value="">None</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->store_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="form-label">Deal Name <span class="text-red-500">*</span></label>
                    <input type="text" name="deal_name" class="form-input w-full" required>
                </div>
                <div class="col-span-2">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-input w-full"></textarea>
                </div>
                <div>
                    <label class="form-label">Deal Type <span class="text-red-500">*</span></label>
                    <select name="deal_type" class="form-input w-full" required>
                        <option value="flat_fee">Flat Fee</option>
                        <option value="hybrid">Hybrid</option>
                        <option value="gifting">Gifting</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Hybrid Commission Rate (%)</label>
                    <input type="number" name="hybrid_commission_rate" class="form-input w-full" step="0.01" min="0" max="100">
                </div>
                <div>
                    <label class="form-label">Flat Fee Amount <span class="text-red-500">*</span></label>
                    <input type="number" name="flat_fee_amount" class="form-input w-full" min="0" step="1" required>
                </div>
                <div>
                    <label class="form-label">Currency <span class="text-red-500">*</span></label>
                    <input type="text" name="currency" class="form-input w-full" maxlength="3" placeholder="KWD" required>
                </div>
                <div>
                    <label class="form-label">Content Due</label>
                    <input type="date" name="content_due_at" class="form-input w-full">
                </div>
                <div>
                    <label class="form-label">Payment Due</label>
                    <input type="date" name="payment_due_at" class="form-input w-full">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Negotiation Notes</label>
                    <textarea name="negotiation_notes" rows="2" class="form-input w-full"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
                <button type="submit" id="propose-deal-submit" class="btn btn-primary btn-sm">Propose Deal</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
$(function () {
    const table = $('#influencer-deals-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('admin.influencer-deals.datatable') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: function (d) {
                d.filter_status   = $('#filter-status').val();
                d.filter_marketer = $('#filter-marketer').val();
                d.filter_vendor   = $('#filter-vendor').val();
            }
        },
        columns: [
            {}, {}, {}, {}, {}, {}, {},
            { orderable: false, width: '100px' },
        ],
        pageLength: 25,
        order: [[0, 'asc']],
    });

    $('#filter-status, #filter-marketer, #filter-vendor').on('change', () => table.ajax.reload());
    $('#clear-filters').on('click', function () {
        $('#filter-status, #filter-marketer, #filter-vendor').val('');
        table.ajax.reload();
    });

    $('#propose-deal-btn').on('click', function () {
        $('#propose-deal-form')[0].reset();
        $('#propose-deal-modal').modal('open');
    });

    $('#propose-deal-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#propose-deal-submit');
        $btn.prop('disabled', true).text('Proposing…');

        $.ajax({
            url: '{{ route('admin.influencer-deals.propose') }}',
            type: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
        })
        .done(function (res) {
            window.Toast.success(res.message);
            $('#propose-deal-modal').modal('close');
            table.ajax.reload();
        })
        .fail(function (xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON?.errors ?? {};
                Object.values(errors).flat().forEach(m => window.Toast.error(m));
            } else {
                window.Toast.error(xhr.responseJSON?.message ?? 'Failed to propose deal.');
            }
        })
        .always(() => $btn.prop('disabled', false).text('Propose Deal'));
    });
});
</script>
@endpush
