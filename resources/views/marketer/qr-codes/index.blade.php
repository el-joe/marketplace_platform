@extends('layouts.marketer')

@section('title', __('marketer.qr.title'))
@section('page-title', __('marketer.qr.title'))

@section('content')

    <div x-data="qrManager()" class="space-y-6">

        {{-- Header actions --}}
        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">{{ __('marketer.qr.subtitle') }}</p>
            <button type="button" @click="showModal = true" class="btn btn-primary">{{ __('marketer.qr.generate_qr_code') }}</button>
        </div>

        {{-- Grid --}}
        @if($qrCodes->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
                <p class="text-5xl mb-4">🔲</p>
                <p class="font-semibold text-gray-700">{{ __('marketer.qr.no_qr_codes') }}</p>
                <p class="text-sm text-gray-400 mt-1">{{ __('marketer.qr.no_qr_codes_desc') }}</p>
                <button type="button" @click="showModal = true" class="btn btn-primary mt-4">{{ __('marketer.qr.generate_new') }}</button>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($qrCodes as $qr)
                    <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center hover:shadow-md transition-shadow">
                        @if($qr->qr_code_path && \Illuminate\Support\Facades\Storage::exists($qr->qr_code_path))
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($qr->qr_code_path) }}" alt="QR"
                                class="w-28 h-28 mx-auto rounded-lg border border-gray-100 mb-3">
                        @else
                            <div
                                class="w-28 h-28 mx-auto rounded-lg border border-gray-100 bg-gray-50 flex items-center justify-center mb-3 text-gray-300 text-3xl">
                                🔲</div>
                        @endif

                        <p class="text-sm font-semibold text-gray-800 truncate">
                            {{ $qr->custom_label ?: $qr->getTypeLabel() }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $qr->getTypeLabel() }}
                        </p>
                        <p class="text-xs font-semibold text-blue-600 mt-1">
                            {{ __('marketer.qr.scans', ['count' => number_format($qr->scan_count)]) }}
                        </p>

                        <div class="flex gap-1 mt-3">
                            <a href="{{ route('marketer.qr-codes.download', $qr) }}" class="flex-1 btn btn-xs btn-secondary">{{ __('marketer.qr.download') }}</a>
                            <button type="button" @click="copyBarcode('{{ $qr->barcode_value }}')"
                                class="flex-1 btn btn-xs btn-secondary">{{ __('marketer.qr.copy_url') }}</button>
                        </div>
                        <p class="text-xs text-gray-300 mt-2">{{ $qr->created_at->format('d M Y') }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div>{{ $qrCodes->links() }}</div>
        @endif

        {{-- Generate Modal --}}
        <div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-xl" @click.stop>
                <div class="flex items-center justify-between p-5 border-b border-gray-200">
                    <h3 class="font-bold text-gray-800">{{ __('marketer.qr.generate_new') }}</h3>
                    <button type="button" @click="showModal = false; qrResult = null"
                        class="text-gray-400 hover:text-gray-700">✕</button>
                </div>

                <div class="p-5 space-y-4" x-show="!qrResult">
                    <div>
                        <label class="form-label text-xs">{{ __('marketer.qr.qr_type') }}</label>
                        <select x-model="form.code_type" class="form-input text-sm py-2">
                            <option value="marketer_profile">{{ __('marketer.qr.type_profile') }}</option>
                            <option value="campaign">{{ __('marketer.qr.type_campaign') }}</option>
                            <option value="product">{{ __('marketer.qr.type_product') }}</option>
                            <option value="vendor_store">{{ __('marketer.qr.type_vendor_store') }}</option>
                            <option value="whatsapp_link">{{ __('marketer.qr.type_whatsapp_link') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-xs">{{ __('marketer.qr.target_url') }}</label>
                        <input type="url" x-model="form.target_url" class="form-input text-sm py-2"
                            placeholder="https://..." required>
                    </div>
                    <div>
                        <label class="form-label text-xs">{{ __('marketer.qr.label_optional') }}</label>
                        <input type="text" x-model="form.custom_label" class="form-input text-sm py-2"
                            placeholder="{{ __('marketer.qr.label_placeholder') }}">
                    </div>
                    <div x-show="form.code_type === 'campaign' || form.code_type === 'product'">
                        <label class="form-label text-xs">{{ __('marketer.qr.campaign_id_optional') }}</label>
                        <input type="text" x-model="form.campaign_id" class="form-input text-sm py-2" placeholder="UUID">
                    </div>

                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="generate()" :disabled="loading" class="btn btn-primary flex-1"
                            x-text="loading ? @js(__('marketer.qr.generating')) : @js(__('marketer.qr.generate'))"></button>
                        <button type="button" @click="showModal = false" class="btn btn-secondary flex-1">{{ __('marketer.qr.cancel') }}</button>
                    </div>
                </div>

                {{-- Success state --}}
                <div class="p-5 text-center" x-show="qrResult" x-cloak>
                    <img :src="qrResult?.qr_url" class="w-40 h-40 mx-auto rounded-xl border border-gray-200 mb-4" alt="QR">
                    <p class="text-sm text-gray-700 font-medium mb-3">{{ __('marketer.qr.generated') }}</p>
                    <div class="flex gap-2">
                        <a :href="qrResult?.download_url" download class="btn btn-primary flex-1">{{ __('marketer.qr.download') }}</a>
                        <button type="button"
                            @click="showModal = false; qrResult = null; $nextTick(() => location.reload())"
                            class="btn btn-secondary flex-1">{{ __('marketer.qr.done') }}</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection

@push('scripts')
    <script>
        function qrManager() {
            return {
                showModal: false, loading: false, qrResult: null,
                form: {
                    code_type: 'marketer_profile',
                    target_url: '',
                    custom_label: '',
                    campaign_id: '',
                },
                async generate() {
                    if (!this.form.target_url) return;
                    this.loading = true;
                    try {
                        const res = await fetch('{{ route('marketer.qr-codes.generate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(this.form),
                        });
                        const data = await res.json();
                        if (data.success) this.qrResult = data.data;
                        else alert(data.message || @json(__('marketer.qr.error_generating')));
                    } catch (e) { alert(@json(__('marketer.campaigns.network_error_title'))); }
                    finally { this.loading = false; }
                },
                copyBarcode(val) {
                    navigator.clipboard.writeText(val)
                        .then(() => alert(@json(__('marketer.qr.copied_to_clipboard'))));
                },
            };
        }
    </script>
@endpush
