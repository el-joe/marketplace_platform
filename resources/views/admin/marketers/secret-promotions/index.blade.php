@extends('layouts.admin')

@section('title', __('admin.marketers.secret_promotions'))

@section('content')

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ __('admin.marketers.secret_promotions') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('admin.marketers.hidden_splits_desc') }}</p>
        </div>
        <button type="button" class="btn btn-primary" id="btn-add-promo">{{ __('admin.marketers.add_promotion') }}</button>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium text-gray-500">{{ __('admin.marketers.listing') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-gray-500">{{ __('admin.marketers.vendor') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-gray-500">{{ __('admin.marketers.marketer') }}</th>
                        <th class="px-4 py-3 text-end font-medium text-gray-500">{{ __('admin.marketers.total_pct') }}</th>
                        <th class="px-4 py-3 text-end font-medium text-gray-500">{{ __('admin.marketers.marketer_pct') }}</th>
                        <th class="px-4 py-3 text-end font-medium text-gray-500">{{ __('admin.marketers.platform_pct') }}</th>
                        <th class="px-4 py-3 font-medium text-gray-500">{{ __('admin.status') }}</th>
                        <th class="px-4 py-3 font-medium text-gray-500">{{ __('admin.marketers.valid_until') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($promotions as $promo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $promo->vendorListing?->product?->name_en ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $promo->vendor?->store_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                @if ($promo->marketer)
                                    {{ $promo->marketer->name }}
                                @else
                                    <span class="text-xs text-gray-400 italic">{{ __('admin.marketers.any') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-end font-mono font-bold">{{ $promo->total_commission_pct }}%</td>
                            <td class="px-4 py-3 text-end font-mono text-green-600">{{ $promo->marketer_share_pct }}%</td>
                            <td class="px-4 py-3 text-end font-mono text-blue-600">{{ $promo->admin_share_pct }}%</td>
                            <td class="px-4 py-3">
                                <span
                                    class="badge badge-{{ $promo->status === \App\Enums\SecretPromotionStatus::Active ? 'success' : ($promo->status === \App\Enums\SecretPromotionStatus::Paused ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($promo->status->value) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $promo->valid_until?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400 italic">{{ __('admin.marketers.no_secret_promotions') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
                <h3 class="font-bold text-gray-800">{{ __('admin.marketers.new_secret_promotion_title') }}</h3>
                <button type="button" class="text-gray-400 hover:text-gray-700" id="btn-close-promo-modal">✕</button>
            </div>
            <form id="form-add-promo" class="p-5 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">{{ __('admin.marketers.vendor') }}</label>
                        <input type="text" name="vendor_id" class="form-input" placeholder="{{ __('admin.marketers.vendor_id_placeholder') }}" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketers.listing_id') }}</label>
                        <input type="text" name="vendor_listing_id" class="form-input" placeholder="{{ __('admin.marketers.listing_id_placeholder') }}" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketers.marketer_optional') }}</label>
                        <input type="text" name="marketer_id" class="form-input" placeholder="{{ __('admin.marketers.marketer_optional_placeholder') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketers.product_value') }}</label>
                        <input type="number" name="product_value" class="form-input" required min="1">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketers.total_commission_pct') }}</label>
                        <input type="number" name="total_commission_pct" class="form-input" step="0.01" required min="0"
                            max="100">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketers.marketer_share_pct') }}</label>
                        <input type="number" name="marketer_share_pct" class="form-input" step="0.01" required min="0"
                            max="100">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketers.platform_share_pct') }}</label>
                        <input type="number" name="admin_share_pct" class="form-input" step="0.01" required min="0"
                            max="100">
                    </div>
                    <div>
                        <label class="form-label">{{ __('admin.marketers.min_commission_pct') }}</label>
                        <input type="number" name="min_commission_pct" class="form-input" step="0.01" required min="0">
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">{{ __('admin.marketers.valid_until_optional') }}</label>
                        <input type="date" name="valid_until" class="form-input">
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="btn btn-primary flex-1">{{ __('admin.marketers.create') }}</button>
                    <button type="button" id="btn-cancel-promo" class="btn btn-secondary flex-1">{{ __('common.cancel') }}</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.TRANSLATIONS = window.TRANSLATIONS || {};
        Object.assign(window.TRANSLATIONS, {
            validationError: @json(__('admin.marketers.validation_error')),
        });
    </script>
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
                    .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.validationError));
            });
        });
    </script>
@endpush
