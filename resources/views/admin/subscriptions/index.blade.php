@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Vendor Subscriptions')

@section('content')


    {{-- ─── Stats ───────────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total</p>
            <p class="text-2xl font-extrabold text-gray-800">{{ number_format($stats['total']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-green-500 uppercase tracking-wide mb-1">Active</p>
            <p class="text-2xl font-extrabold text-green-600">{{ number_format($stats['active']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Expired</p>
            <p class="text-2xl font-extrabold text-gray-600">{{ number_format($stats['expired']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 p-4 text-center">
            <p class="text-xs text-red-400 uppercase tracking-wide mb-1">Cancelled</p>
            <p class="text-2xl font-extrabold text-red-500">{{ number_format($stats['cancelled']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-green-50 p-4 text-center bg-green-50">
            <p class="text-xs text-green-600 uppercase tracking-wide mb-1">MRR</p>
            <p class="text-2xl font-extrabold text-green-700">{{ number_format($stats['mrr_cents'] / 100) }} EGP</p>
        </div>
    </div>

    {{-- ─── Filters + Actions ───────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4 mb-4 flex flex-wrap gap-3 items-end justify-between">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="label-sm">Status</label>
                <select id="filter-status" class="form-select text-sm py-1.5 pr-8">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="trialing">Trialing</option>
                    <option value="past_due">Past Due</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
            <div>
                <label class="label-sm">Plan</label>
                <select id="filter-plan" class="form-select text-sm py-1.5 pr-8">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}">{{ $plan->name_en }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="button" id="btn-subscribe-vendor" class="btn btn-primary btn-sm">+ Subscribe Vendor</button>
    </div>

    {{-- ─── DataTable ───────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <table id="tbl-subscriptions" class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Vendor</th>
                    <th class="px-4 py-3 text-left">Plan</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Period</th>
                    <th class="px-4 py-3 text-left">Listings</th>
                    <th class="px-4 py-3 text-left">Auto-Renew</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    {{-- ─── Subscribe Vendor Modal ──────────────────────────────────────────────── --}}
    <div id="subscribe-modal" class="modal" style="display:none;">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-lg mb-4">Subscribe a Vendor</h3>

            <div class="space-y-3">
                <div>
                    <label class="label-sm">Vendor ID or search <span class="text-red-500">*</span></label>
                    <input type="text" id="sv-vendor-id" class="form-input w-full text-sm"
                        placeholder="Paste vendor UUID...">
                </div>
                <div>
                    <label class="label-sm">Plan <span class="text-red-500">*</span></label>
                    <select id="sv-plan-id" class="form-select w-full text-sm">
                        <option value="">— select plan —</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name_en }} — {{ number_format($plan->price_cents / 100) }}
                                {{ $plan->currency }}/mo</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-5 pt-4 border-t border-gray-100">
                <button type="button" id="subscribe-modal-cancel" class="btn btn-ghost btn-sm">Cancel</button>
                <button type="button" id="subscribe-modal-save" class="btn btn-primary btn-sm px-8">Subscribe</button>
            </div>
        </div>
    </div>

    {{-- ─── Cancel Subscription Modal ───────────────────────────────────────────── --}}
    <div id="cancel-sub-modal" class="modal" style="display:none;">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-lg mb-4">Cancel Subscription</h3>
            <input type="hidden" id="cancel-sub-id">
            <div>
                <label class="label-sm">Reason (optional)</label>
                <textarea id="cancel-sub-reason" rows="3" class="form-input w-full text-sm"
                    placeholder="Reason for cancellation..."></textarea>
            </div>
            <div class="flex gap-3 justify-end mt-5 pt-4 border-t border-gray-100">
                <button type="button" id="cancel-sub-modal-close" class="btn btn-ghost btn-sm">Close</button>
                <button type="button" id="cancel-sub-modal-confirm" class="btn btn-danger btn-sm">Cancel
                    Subscription</button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(function () {
            const tok = '{{ csrf_token() }}';

            const tbl = $('#tbl-subscriptions').DataTable({
                serverSide: true,
                processing: true,
                pageLength: 25,
                order: [[3, 'desc']],
                ajax: {
                    url: '{{ route('admin.subscriptions.datatable') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok },
                    data: d => {
                        d.status = $('#filter-status').val();
                        d.plan_id = $('#filter-plan').val();
                    },
                },
                columns: [
                    { data: 'vendor', orderable: false },
                    { data: 'plan', orderable: false },
                    { data: 'status', orderable: false },
                    { data: 'period', orderable: false },
                    { data: 'listings', orderable: false },
                    { data: 'auto_renew', orderable: false },
                    { data: 'actions', orderable: false },
                ],
                language: { processing: 'Loading…' },
            });

            $('#filter-status, #filter-plan').on('change', () => tbl.ajax.reload());

            // ── Subscribe vendor ───────────────────────────────────────────────────────
            $('#btn-subscribe-vendor').on('click', () => {
                $('#sv-vendor-id').val('');
                $('#sv-plan-id').val('');
                $('#subscribe-modal').show();
            });
            $('#subscribe-modal-cancel').on('click', () => $('#subscribe-modal').hide());
            $('#subscribe-modal-save').on('click', function () {
                fetch('{{ route('admin.subscriptions.subscribe-vendor') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        vendor_id: $('#sv-vendor-id').val(),
                        plan_id: $('#sv-plan-id').val(),
                    }),
                }).then(r => r.json()).then(data => {
                    if (data.success) { window.Toast.success(data.message); $('#subscribe-modal').hide(); tbl.ajax.reload(); }
                    else { window.Toast.error(data.message ?? 'Error'); }
                });
            });

            // ── Cancel subscription ────────────────────────────────────────────────────
            $(document).on('click', '.btn-cancel-sub', function () {
                $('#cancel-sub-id').val($(this).data('id'));
                $('#cancel-sub-reason').val('');
                $('#cancel-sub-modal').show();
            });
            $('#cancel-sub-modal-close').on('click', () => $('#cancel-sub-modal').hide());
            $('#cancel-sub-modal-confirm').on('click', function () {
                const id = $('#cancel-sub-id').val();
                fetch('/admin/subscriptions/' + id + '/cancel', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': tok, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reason: $('#cancel-sub-reason').val() }),
                }).then(r => r.json()).then(data => {
                    if (data.success) { window.Toast.success(data.message); $('#cancel-sub-modal').hide(); tbl.ajax.reload(); }
                    else { window.Toast.error(data.message); }
                });
            });
        });
    </script>
@endpush
