@extends('layouts.admin')

@section('title', $deal->deal_name)

@section('content')

@php
    $statusColor = match ($deal->status?->value ?? $deal->status) {
        'proposed', 'negotiating' => 'warning',
        'accepted', 'in_progress' => 'primary',
        'content_submitted' => 'info',
        'approved', 'paid' => 'success',
        'cancelled', 'rejected' => 'danger',
        default => 'secondary',
    };
    $steps = ['proposed', 'negotiating', 'accepted', 'in_progress', 'content_submitted', 'approved', 'paid'];
    $currentStep = array_search($deal->status?->value ?? $deal->status, $steps, true);
@endphp

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <a href="{{ route('admin.influencer-deals.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{!! __('admin.influencer_deal_show_section.back_to_deals') !!}</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $deal->deal_name }}</h1>
        <span class="badge badge-{{ $statusColor }} mt-1">{{ ucfirst(str_replace('_', ' ', $deal->status?->value ?? $deal->status)) }}</span>
    </div>
    <div class="flex gap-2" id="deal-actions">
        @if(($deal->status?->value ?? $deal->status) === 'content_submitted')
            <button type="button" id="approve-deal-btn" class="btn btn-success btn-sm">{{ __('admin.influencer_deal_show_section.approve_deal') }}</button>
        @endif
        @if(($deal->status?->value ?? $deal->status) === 'approved')
            <button type="button" id="initiate-payment-btn" class="btn btn-primary btn-sm">{{ __('admin.influencer_deal_show_section.initiate_payment') }}</button>
        @endif
        @if(!in_array($deal->status?->value ?? $deal->status, ['paid', 'cancelled', 'rejected'], true))
            <button type="button" id="cancel-deal-btn" class="btn btn-danger btn-sm">{{ __('admin.influencer_deal_show_section.cancel') }}</button>
        @endif
    </div>
</div>

{{-- ─── Status Stepper ─────────────────────────────────────────────────────── --}}
@if(!in_array($deal->status?->value ?? $deal->status, ['cancelled', 'rejected'], true))
<div class="flex items-center gap-2 mb-6 overflow-x-auto">
    @foreach($steps as $i => $step)
        <div class="flex items-center gap-2 shrink-0">
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $i <= $currentStep ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500' }}">{{ $i + 1 }}</div>
            <span class="text-xs {{ $i <= $currentStep ? 'text-gray-800 font-medium' : 'text-gray-400' }}">{{ ucfirst(str_replace('_', ' ', $step)) }}</span>
            @if(!$loop->last)<div class="w-6 h-px bg-gray-300"></div>@endif
        </div>
    @endforeach
</div>
@endif

<div class="grid grid-cols-3 gap-5">
    {{-- ─── Deal Details ───────────────────────────────────────────────────── --}}
    <x-card class="col-span-1">
        <h3 class="font-semibold text-gray-900 mb-4">{{ __('admin.influencer_deal_show_section.deal_details') }}</h3>
        <dl class="space-y-3 text-sm">
            <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.marketer_label') }}</dt><dd class="font-medium">{{ $deal->marketer->name }} <span class="badge badge-primary">{{ __('admin.marketer_types.' . ($deal->marketer->type?->value ?? $deal->marketer->type)) }}</span></dd></div>
            <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.vendor_label') }}</dt><dd class="font-medium">{{ $deal->vendor->store_name ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.deal_type_label') }}</dt><dd class="font-medium">{{ ucfirst(str_replace('_',' ', $deal->deal_type?->value ?? $deal->deal_type)) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.flat_fee_label') }}</dt><dd class="font-medium">{{ number_format($deal->flat_fee_amount) }} {{ $deal->currency }}</dd></div>
            @if($deal->hybrid_commission_rate)
                <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.hybrid_commission_rate_label') }}</dt><dd class="font-medium">{{ $deal->hybrid_commission_rate }}%</dd></div>
            @endif
            <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.proposed_by_label') }}</dt><dd class="font-medium">{{ ucfirst($deal->proposed_by) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.content_due_label') }}</dt><dd class="font-medium">{{ $deal->content_due_at?->format('Y-m-d') ?? '—' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.payment_due_label') }}</dt><dd class="font-medium">{{ $deal->payment_due_at?->format('Y-m-d') ?? '—' }}</dd></div>
            @if($deal->paid_at)
                <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.paid_at_label') }}</dt><dd class="font-medium">{{ $deal->paid_at->format('Y-m-d H:i') }}</dd></div>
            @endif
            @if($deal->approvedByAdmin)
                <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.approved_by_label') }}</dt><dd class="font-medium">{{ $deal->approvedByAdmin->name }}</dd></div>
            @endif
            @if($deal->cancellation_reason)
                <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.cancellation_reason_label') }}</dt><dd class="font-medium text-red-600">{{ $deal->cancellation_reason }}</dd></div>
            @endif
            @if($deal->description)
                <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.description_label') }}</dt><dd>{{ $deal->description }}</dd></div>
            @endif
            @if($deal->negotiation_notes)
                <div><dt class="text-gray-500">{{ __('admin.influencer_deal_show_section.negotiation_notes_label') }}</dt><dd>{{ $deal->negotiation_notes }}</dd></div>
            @endif
        </dl>
    </x-card>

    {{-- ─── Deliverables ───────────────────────────────────────────────────── --}}
    <x-card class="col-span-2">
        <h3 class="font-semibold text-gray-900 mb-4">{{ __('admin.influencer_deal_show_section.deliverables_title') }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 text-xs uppercase">
                        <th class="pb-2">{{ __('admin.influencer_deal_show_section.platform_col') }}</th>
                        <th class="pb-2">{{ __('admin.influencer_deal_show_section.content_type_col') }}</th>
                        <th class="pb-2">{{ __('admin.influencer_deal_show_section.status_col') }}</th>
                        <th class="pb-2">{{ __('admin.influencer_deal_show_section.submitted_url_col') }}</th>
                        <th class="pb-2">{{ __('admin.influencer_deal_show_section.actions_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deal->deliverables as $deliverable)
                        <tr class="border-t border-gray-100" data-deliverable-id="{{ $deliverable->id }}">
                            <td class="py-2">{{ ucfirst(str_replace('_', ' ', $deliverable->platform?->value ?? $deliverable->platform)) }}</td>
                            <td class="py-2">{{ ucfirst($deliverable->content_type?->value ?? $deliverable->content_type) }}</td>
                            <td class="py-2">
                                <span class="badge badge-{{ match($deliverable->status?->value ?? $deliverable->status) {
                                    'approved' => 'success',
                                    'submitted' => 'primary',
                                    'rejected' => 'danger',
                                    'overdue' => 'danger',
                                    default => 'secondary',
                                } }}">{{ ucfirst($deliverable->status?->value ?? $deliverable->status) }}</span>
                            </td>
                            <td class="py-2">
                                @if($deliverable->content_url)
                                    <a href="{{ $deliverable->content_url }}" target="_blank" class="text-primary-600 hover:underline text-xs">{{ __('admin.influencer_deal_show_section.view') }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2">
                                @if(($deliverable->status?->value ?? $deliverable->status) === 'submitted')
                                    <div class="flex gap-1">
                                        <button type="button" class="btn btn-xs btn-success btn-approve-deliverable" data-id="{{ $deliverable->id }}">{{ __('admin.influencer_deal_show_section.approve') }}</button>
                                        <button type="button" class="btn btn-xs btn-danger btn-reject-deliverable" data-id="{{ $deliverable->id }}">{{ __('admin.influencer_deal_show_section.reject') }}</button>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-gray-400">{{ __('admin.influencer_deal_show_section.no_deliverables_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

{{-- ─── Cancel Modal ────────────────────────────────────────────────────────── --}}
<div id="cancel-deal-modal" class="modal" style="display:none;">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">{{ __('admin.influencer_deal_show_section.cancel_deal_title') }}</h3>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.influencer_deal_show_section.reason_label') }}</label>
            <textarea id="cancel-reason" rows="3" class="form-input w-full text-sm" placeholder="{{ __('admin.influencer_deal_show_section.cancellation_reason_placeholder') }}"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="cancel-cancel-deal" class="btn btn-ghost btn-sm">{{ __('admin.influencer_deal_show_section.close') }}</button>
            <button type="button" id="confirm-cancel-deal" class="btn btn-danger btn-sm">{{ __('admin.influencer_deal_show_section.cancel_deal') }}</button>
        </div>
    </div>
</div>

{{-- ─── Reject Deliverable Modal ───────────────────────────────────────────── --}}
<div id="reject-deliverable-modal" class="modal" style="display:none;">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">{{ __('admin.influencer_deal_show_section.reject_deliverable_title') }}</h3>
        <input type="hidden" id="reject-deliverable-id">
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.influencer_deal_show_section.reason_label') }}</label>
            <textarea id="reject-deliverable-reason" rows="3" class="form-input w-full text-sm" placeholder="{{ __('admin.influencer_deal_show_section.rejection_reason_placeholder') }}"></textarea>
        </div>
        <div class="flex gap-3 justify-end">
            <button type="button" id="cancel-reject-deliverable" class="btn btn-ghost btn-sm">{{ __('admin.influencer_deal_show_section.close') }}</button>
            <button type="button" id="confirm-reject-deliverable" class="btn btn-danger btn-sm">{{ __('admin.influencer_deal_show_section.reject') }}</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
window.TRANSLATIONS = window.TRANSLATIONS || {};
Object.assign(window.TRANSLATIONS, {
    approveDealConfirmTitle: @json(__('admin.influencer_deal_show_section.approve_deal_confirm_title')),
    approveDealConfirmText: @json(__('admin.influencer_deal_show_section.approve_deal_confirm_text')),
    approve: @json(__('admin.influencer_deal_show_section.approve')),
    initiatePaymentConfirmTitle: @json(__('admin.influencer_deal_show_section.initiate_payment_confirm_title')),
    initiatePaymentConfirmText: @json(__('admin.influencer_deal_show_section.initiate_payment_confirm_text')),
    initiate: @json(__('admin.influencer_deal_show_section.initiate')),
    pleaseEnterReason: @json(__('admin.influencer_deal_show_section.please_enter_reason')),
    somethingWentWrong: @json(__('admin.influencer_deal_show_section.something_went_wrong')),
});

document.addEventListener('DOMContentLoaded', function () {
    const dealId = '{{ $deal->id }}';
    const csrf = '{{ csrf_token() }}';

    $('#approve-deal-btn').on('click', function () {
        window.confirmDialog({ title: window.TRANSLATIONS.approveDealConfirmTitle, text: window.TRANSLATIONS.approveDealConfirmText, confirmButtonText: window.TRANSLATIONS.approve })
            .then(confirmed => {
                if (!confirmed) return;
                $.post(`{{ url('/influencer-deals') }}/${dealId}/approve`, { _token: csrf })
                    .done(r => { window.Toast.success(r.message); window.location.reload(); })
                    .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.somethingWentWrong));
            });
    });

    $('#initiate-payment-btn').on('click', function () {
        window.confirmDialog({ title: window.TRANSLATIONS.initiatePaymentConfirmTitle, text: window.TRANSLATIONS.initiatePaymentConfirmText, confirmButtonText: window.TRANSLATIONS.initiate })
            .then(confirmed => {
                if (!confirmed) return;
                $.post(`{{ url('/influencer-deals') }}/${dealId}/payment`, { _token: csrf })
                    .done(r => { window.Toast.success(r.message); window.location.reload(); })
                    .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.somethingWentWrong));
            });
    });

    $('#cancel-deal-btn').on('click', () => { $('#cancel-reason').val(''); $('#cancel-deal-modal').show(); });
    $('#cancel-cancel-deal').on('click', () => $('#cancel-deal-modal').hide());
    $('#confirm-cancel-deal').on('click', function () {
        const reason = $('#cancel-reason').val().trim();
        if (!reason) { window.Toast.warning(window.TRANSLATIONS.pleaseEnterReason); return; }
        $.post(`{{ url('/influencer-deals') }}/${dealId}/cancel`, { _token: csrf, reason })
            .done(r => { window.Toast.success(r.message); window.location.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.somethingWentWrong));
    });

    $(document).on('click', '.btn-approve-deliverable', function () {
        const deliverableId = $(this).data('id');
        $.post(`{{ url('/influencer-deals') }}/${dealId}/deliverables/${deliverableId}/approve`, { _token: csrf })
            .done(r => { window.Toast.success(r.message); window.location.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.somethingWentWrong));
    });

    $(document).on('click', '.btn-reject-deliverable', function () {
        $('#reject-deliverable-id').val($(this).data('id'));
        $('#reject-deliverable-reason').val('');
        $('#reject-deliverable-modal').show();
    });
    $('#cancel-reject-deliverable').on('click', () => $('#reject-deliverable-modal').hide());
    $('#confirm-reject-deliverable').on('click', function () {
        const deliverableId = $('#reject-deliverable-id').val();
        const reason = $('#reject-deliverable-reason').val().trim();
        if (!reason) { window.Toast.warning(window.TRANSLATIONS.pleaseEnterReason); return; }
        $.post(`{{ url('/influencer-deals') }}/${dealId}/deliverables/${deliverableId}/reject`, { _token: csrf, reason })
            .done(r => { window.Toast.success(r.message); window.location.reload(); })
            .fail(xhr => window.Toast.error(xhr.responseJSON?.message || window.TRANSLATIONS.somethingWentWrong));
    });
}, { once: true });
</script>
@endpush
