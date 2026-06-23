@extends('layouts.admin')

@section('title', 'Secret Promotions')

@section('content')

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Secret Promotions</h1>
            <p class="text-sm text-gray-500 mt-1">Hidden commission splits — marketers see only their share; customers see
                nothing.</p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-add-promo">+ Add Promotion</button>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Listing</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Vendor</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Marketer</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500">Total %</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500">Marketer %</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500">Platform %</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3 font-medium text-gray-500">Valid Until</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($promotions as $promo)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $promo->vendorListing?->product?->name_en ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $promo->vendor?->store_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $promo->marketer?->name ?? '<span class="text-xs text-gray-400 italic">Any</span>' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold">{{ $promo->total_commission_pct }}%</td>
                        <td class="px-4 py-3 text-right font-mono text-green-600">{{ $promo->marketer_share_pct }}%</td>
                        <td class="px-4 py-3 text-right font-mono text-blue-600">{{ $promo->admin_share_pct }}%</td>
                        <td class="px-4 py-3">
                            <span
                                class="badge badge-{{ $promo->status === 'active' ? 'success' : ($promo->status === 'paused' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($promo->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $promo->valid_until?->format('d M Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-400 italic">No secret promotions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($promotions->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $promotions->links() }}
            </div>
        @endif
    </div>

    {{-- Add Promotion Modal --}}
    <div id="modal-add-promo" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl">
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <h3 class="font-bold text-gray-800">New Secret Promotion</h3>
                <button type="button" class="text-gray-400 hover:text-gray-700" id="btn-close-promo-modal">✕</button>
            </div>
            <form id="form-add-promo" class="p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Vendor</label>
                        <input type="text" name="vendor_id" class="form-input" placeholder="Vendor UUID" required>
                    </div>
                    <div>
                        <label class="form-label">Listing ID</label>
                        <input type="text" name="vendor_listing_id" class="form-input" placeholder="Listing UUID" required>
                    </div>
                    <div>
                        <label class="form-label">Marketer (optional)</label>
                        <input type="text" name="marketer_id" class="form-input" placeholder="Leave blank = any">
                    </div>
                    <div>
                        <label class="form-label">Product Value (cents)</label>
                        <input type="number" name="product_value_cents" class="form-input" required min="1">
                    </div>
                    <div>
                        <label class="form-label">Total Commission %</label>
                        <input type="number" name="total_commission_pct" class="form-input" step="0.01" required min="0"
                            max="100">
                    </div>
                    <div>
                        <label class="form-label">Marketer Share %</label>
                        <input type="number" name="marketer_share_pct" class="form-input" step="0.01" required min="0"
                            max="100">
                    </div>
                    <div>
                        <label class="form-label">Platform Share %</label>
                        <input type="number" name="admin_share_pct" class="form-input" step="0.01" required min="0"
                            max="100">
                    </div>
                    <div>
                        <label class="form-label">Min Commission %</label>
                        <input type="number" name="min_commission_pct" class="form-input" step="0.01" required min="0">
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">Valid Until (optional)</label>
                        <input type="date" name="valid_until" class="form-input">
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary flex-1">Create</button>
                    <button type="button" id="btn-cancel-promo" class="btn btn-secondary flex-1">Cancel</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="module">
        $(function () {
            const tok = '{{ csrf_token() }}';

            $('#btn-add-promo, #btn-cancel-promo, #btn-close-promo-modal').on('click', function () {
                $('#modal-add-promo').toggleClass('hidden', !$(this).is('#btn-add-promo'));
            });

            $('#form-add-promo').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '{{ route('admin.marketers.secret.store') }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: { 'X-CSRF-TOKEN': tok },
                })
                    .done(r => {
                        window.Toast.success(r.message);
                        $('#modal-add-promo').addClass('hidden');
                        setTimeout(() => location.reload(), 1200);
                    })
                    .fail(xhr => window.Toast.error(xhr.responseJSON?.message || 'Validation error'));
            });
        });
    </script>
@endpush