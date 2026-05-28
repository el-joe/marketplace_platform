@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/select2.js', 'resources/js/admin/vendor-applications.js'])
@endpush

@section('title', 'Review: ' . $vendor->store_name)

@section('content')

    {{-- ─── Header ───────────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.vendor-applications.index') }}" class="hover:text-primary-600">Applications</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">{{ $vendor->store_name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $vendor->store_name }}</h1>
            <div class="flex items-center gap-3 mt-1">
                @php
                    $statusColors = ['pending' => 'warning', 'under_review' => 'primary', 'active' => 'success', 'rejected' => 'danger'];
                    $sc = $statusColors[$vendor->global_status] ?? 'gray';
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $sc }}-100 text-{{ $sc }}-700">
                    {{ ucwords(str_replace('_', ' ', $vendor->global_status)) }}
                </span>
                @php
                    $urgencyClass = $daysWaiting > 5 ? 'text-red-600 font-semibold' : ($daysWaiting >= 2 ? 'text-yellow-600' : 'text-green-600');
                @endphp
                <span class="text-sm {{ $urgencyClass }}">Waiting {{ $daysWaiting }} day{{ $daysWaiting !== 1 ? 's' : '' }}</span>
                <button type="button"
                    id="start-review-btn"
                    class="btn btn-secondary btn-xs {{ $vendor->global_status === 'under_review' ? 'hidden' : '' }}"
                    data-url="{{ route('admin.vendor-applications.start-review', $vendor->id) }}">
                    Start Review
                </button>
                <button type="button"
                    id="assign-me-btn"
                    class="btn btn-ghost btn-xs"
                    data-url="{{ route('admin.vendor-applications.assign-me', $vendor->id) }}"
                    title="Assign yourself as account manager">
                    Assign to me
                </button>
            </div>
        </div>
        <a href="{{ route('admin.vendor-applications.index') }}" class="btn btn-secondary btn-sm">← Back to Queue</a>
    </div>

    {{-- ─── Main 2-column layout ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-12 gap-6">

        {{-- ═══════ LEFT: Review Content (7 cols) ═══════ --}}
        <div class="col-span-12 lg:col-span-7 space-y-6">

            {{-- ─── Business Information ────────────────────────────────────────────── --}}
            <x-card title="Business Information">
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Business Name</dt>
                        <dd class="font-medium">{{ $vendor->business_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Business Type</dt>
                        <dd>{{ ucfirst($vendor->business_type ?? '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Registration No.</dt>
                        <dd class="font-mono text-xs">{{ $vendor->business_registration_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Tax ID</dt>
                        <dd class="font-mono text-xs">{{ $vendor->tax_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Contact Email</dt>
                        <dd class="break-all">{{ $vendor->contact_email ?? $vendor->email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Contact Phone</dt>
                        <dd>{{ $vendor->contact_phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">WhatsApp</dt>
                        <dd>{{ $vendor->whatsapp_number ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Country</dt>
                        <dd>{{ $vendor->country?->flag_emoji ?? '' }} {{ $vendor->country?->name_en ?? '—' }}</dd>
                    </div>
                    @php $address = $vendor->addresses->first(); @endphp
                    @if($address)
                        <div class="col-span-2">
                            <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Business Address</dt>
                            <dd>
                                {{ $address->street_line_1 ?? '' }}
                                @if($address->street_line_2), {{ $address->street_line_2 }}@endif
                                @if($address->city), {{ $address->city }}@endif
                                @if($address->state), {{ $address->state }}@endif
                                @if($address->postal_code), {{ $address->postal_code }}@endif
                            </dd>
                        </div>
                    @endif
                    @if($vendor->store_description)
                        <div class="col-span-2">
                            <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Store Description</dt>
                            <dd class="text-gray-600 text-sm">{{ $vendor->store_description }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>

            {{-- ─── Documents ────────────────────────────────────────────────────────── --}}
            <x-card title="Documents" subtitle="Required: Business License, Tax Certificate, Owner ID">

                {{-- Required docs checklist --}}
                <div class="flex flex-wrap gap-3 mb-4">
                    @foreach($requiredDocs as $type => $info)
                        <div class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium
                            {{ $info['verified'] ? 'bg-green-100 text-green-700' : ($info['uploaded'] ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            @if($info['verified'])
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @elseif($info['uploaded'])
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                            @else
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            @endif
                            {{ $info['label'] }}
                        </div>
                    @endforeach
                </div>

                {{-- All documents table --}}
                @if($vendor->documents->isEmpty())
                    <p class="text-sm text-gray-400 py-4 text-center">No documents uploaded.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-3 text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="py-2 pr-3 text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="py-2 pr-3 text-xs font-medium text-gray-500 uppercase">Expires</th>
                                    <th class="py-2 pr-3 text-xs font-medium text-gray-500 uppercase">Verified by</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($vendor->documents as $doc)
                                    @php
                                        $docStatColors = ['verified'=>'success','approved'=>'success','pending'=>'warning','rejected'=>'danger','expired'=>'danger'];
                                        $dc = $docStatColors[$doc->status] ?? 'gray';
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2 pr-3 font-medium">
                                            {{ ucwords(str_replace('_', ' ', $doc->document_type)) }}
                                        </td>
                                        <td class="py-2 pr-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $dc }}-100 text-{{ $dc }}-700">
                                                {{ ucfirst($doc->status) }}
                                            </span>
                                            @if($doc->rejection_reason)
                                                <div class="text-xs text-red-600 mt-0.5">{{ $doc->rejection_reason }}</div>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-gray-500 text-xs">
                                            {{ $doc->expires_at ? $doc->expires_at->format('d M Y') : '—' }}
                                            @if($doc->isExpired())
                                                <span class="text-red-500 ml-1">(expired)</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-xs text-gray-500">
                                            @if($doc->verifiedByAdmin)
                                                {{ $doc->verifiedByAdmin->name }}
                                                @if($doc->verified_at)
                                                    <br><span class="text-gray-400">{{ $doc->verified_at->format('d M Y') }}</span>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            <div class="flex items-center gap-1">
                                                @if($doc->file_path)
                                                    <button type="button"
                                                        class="btn btn-xs btn-secondary js-preview-doc-btn"
                                                        data-file="{{ Storage::url($doc->file_path) }}"
                                                        data-type="{{ $doc->document_type }}"
                                                        data-id="{{ $doc->id }}">
                                                        View
                                                    </button>
                                                @endif
                                                @if(!in_array($doc->status, ['verified','approved']))
                                                    <button type="button"
                                                        class="btn btn-xs btn-success js-verify-doc-btn"
                                                        data-url="{{ route('admin.vendor-applications.documents.verify', $doc->id) }}"
                                                        data-id="{{ $doc->id }}">
                                                        Verify
                                                    </button>
                                                @endif
                                                @if($doc->status !== 'rejected')
                                                    <button type="button"
                                                        class="btn btn-xs btn-danger js-reject-doc-btn"
                                                        data-url="{{ route('admin.vendor-applications.documents.reject', $doc->id) }}"
                                                        data-id="{{ $doc->id }}">
                                                        Reject
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Show missing required doc rows --}}
                @foreach($requiredDocs as $type => $info)
                    @if(!$info['uploaded'])
                        <div class="mt-3 flex items-center gap-3 rounded border border-dashed border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            <span>Missing: <strong>{{ $info['label'] }}</strong> — not yet uploaded</span>
                        </div>
                    @endif
                @endforeach
            </x-card>

            {{-- ─── Bank Account ─────────────────────────────────────────────────────── --}}
            <x-card title="Bank Account">
                @php
                    $primaryBank = $vendor->bankAccounts->where('is_primary', true)->first()
                        ?? $vendor->bankAccounts->first();
                    $bankStatusColors = ['verified'=>'success','pending'=>'warning','rejected'=>'danger','unverified'=>'gray'];
                @endphp
                @if($primaryBank)
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Bank Name</dt>
                            <dd class="font-medium">{{ $primaryBank->bank_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Account Holder</dt>
                            <dd>{{ $primaryBank->account_holder_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">IBAN</dt>
                            <dd class="font-mono text-xs">
                                @if($primaryBank->iban)
                                    ****{{ substr($primaryBank->iban, -4) }}
                                @else —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Status</dt>
                            <dd>
                                @php $bsc = $bankStatusColors[$primaryBank->verification_status] ?? 'gray'; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{{ $bsc }}-100 text-{{ $bsc }}-700">
                                    {{ ucfirst($primaryBank->verification_status ?? 'unverified') }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">Currency</dt>
                            <dd class="uppercase">{{ $primaryBank->currency ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-400 uppercase font-medium mb-0.5">SWIFT</dt>
                            <dd class="font-mono text-xs">{{ $primaryBank->swift_code ?? '—' }}</dd>
                        </div>
                    </dl>
                    @if($primaryBank->verification_status !== 'verified')
                        <div class="mt-4">
                            <button type="button"
                                class="btn btn-success btn-sm js-verify-bank-btn"
                                data-url="{{ route('admin.vendors.bank-accounts.verify', [$vendor->id, $primaryBank->id]) }}"
                                data-name="{{ e($primaryBank->bank_name ?? 'bank account') }}">
                                Verify Bank Account
                            </button>
                        </div>
                    @endif
                    @if($vendor->bankAccounts->count() > 1)
                        <p class="text-xs text-gray-400 mt-3">+ {{ $vendor->bankAccounts->count() - 1 }} more account(s)</p>
                    @endif
                @else
                    <div class="flex items-center gap-2 rounded border border-dashed border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        No bank account on file — cannot approve without one.
                    </div>
                @endif
            </x-card>

        </div>{{-- end left column --}}

        {{-- ═══════ RIGHT: Decision Panel (5 cols) ═══════ --}}
        <div class="col-span-12 lg:col-span-5 space-y-5">

            {{-- ─── Review Checklist ─────────────────────────────────────────────────── --}}
            <x-card title="Review Checklist">
                <ul class="space-y-2.5">
                    @foreach($checklist as $key => $item)
                        <li class="flex items-center gap-2.5">
                            @if($item['pass'])
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </span>
                                <span class="text-sm text-gray-700">{{ $item['label'] }}</span>
                            @else
                                <span class="flex-shrink-0 w-5 h-5 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </span>
                                <span class="text-sm text-red-600">{{ $item['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if(!$canApprove)
                    <p class="mt-3 text-xs text-red-500 bg-red-50 rounded px-3 py-2">
                        Resolve all checklist items before approving.
                    </p>
                @endif
            </x-card>

            {{-- ─── Assignment ───────────────────────────────────────────────────────── --}}
            <x-card title="Assignment">
                <div class="space-y-4">
                    <x-form-async-select
                        name="account_manager_admin_id"
                        label="Account Manager"
                        :search-url="route('admin.admins.search')"
                        :value="$vendor->account_manager_admin_id"
                        :value-label="$vendor->accountManagerAdmin?->name"
                        placeholder="Search for admin…"
                        search-param="q" />
                    <x-form-input
                        type="number"
                        name="commission_rate_override_display"
                        label="Commission Rate Override (%)"
                        step="0.01"
                        min="0"
                        max="100"
                        placeholder="Leave empty for category default"
                        :value="$vendor->commission_rate" />
                </div>
            </x-card>

            {{-- ─── Decision ─────────────────────────────────────────────────────────── --}}
            <x-card title="Decision">
                <div class="space-y-2">
                    @if(in_array($vendor->global_status, ['pending', 'under_review']))
                        <button
                            type="button"
                            id="open-approve-modal-btn"
                            class="w-full btn btn-primary {{ !$canApprove ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ !$canApprove ? 'disabled' : '' }}
                            title="{{ !$canApprove ? 'Complete all checklist items first' : '' }}"
                            data-can-approve="{{ $canApprove ? 'true' : 'false' }}"
                            data-vendor-id="{{ $vendor->id }}"
                            data-approve-url="{{ route('admin.vendor-applications.approve', $vendor->id) }}"
                            data-vendor-name="{{ e($vendor->store_name) }}">
                            Approve Application
                        </button>
                        <button
                            type="button"
                            id="open-request-info-modal-btn"
                            class="w-full btn btn-secondary"
                            data-vendor-name="{{ e($vendor->store_name) }}">
                            Request More Information
                        </button>
                        <button
                            type="button"
                            id="open-reject-modal-btn"
                            class="w-full btn btn-danger"
                            data-vendor-name="{{ e($vendor->store_name) }}"
                            data-reject-url="{{ route('admin.vendor-applications.reject', $vendor->id) }}">
                            Reject Application
                        </button>
                    @elseif($vendor->global_status === 'active')
                        <div class="rounded-lg bg-green-50 border border-green-100 px-4 py-3 text-sm text-green-800">
                            ✓ This application was approved.
                        </div>
                    @elseif($vendor->global_status === 'rejected')
                        <div class="rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm text-red-800">
                            ✗ This application was rejected.
                            @if($vendor->rejection_reason)
                                <p class="mt-1 text-xs">{{ $vendor->rejection_reason }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </x-card>

        </div>{{-- end right column --}}
    </div>{{-- end grid --}}

    {{-- ══════════════════════════════════════════════════════════════════════════ --}}
    {{-- ─── MODALS ───────────────────────────────────────────────────────────────── --}}
    {{-- ══════════════════════════════════════════════════════════════════════════ --}}

    {{-- ─── Document Preview Modal ──────────────────────────────────────────────── --}}
    <x-modal id="doc-preview-modal" title="Document Preview" size="xl">
        <div id="doc-preview-content" class="min-h-[400px] flex items-center justify-center bg-gray-50 rounded">
            <p class="text-gray-400 text-sm">Loading…</p>
        </div>
        <div class="flex items-center justify-between mt-4">
            <div class="flex gap-2">
                <button type="button" id="modal-verify-doc-btn" class="btn btn-success btn-sm hidden">Verify Document</button>
                <button type="button" id="modal-reject-doc-open-btn" class="btn btn-danger btn-sm hidden">Reject Document</button>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="$('#doc-preview-modal').modal('close')">Close</button>
        </div>
    </x-modal>

    {{-- ─── Reject Document Modal ────────────────────────────────────────────────── --}}
    <x-modal id="reject-doc-modal" title="Reject Document" size="sm">
        <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-red-500">*</span></label>
        <textarea id="doc-rejection-reason" rows="3" class="form-input w-full text-sm" placeholder="Why is this document being rejected?"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="doc-rejection-error">Reason is required.</p>
        <div class="flex justify-end gap-3 mt-4">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-doc-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-doc-btn" class="btn btn-danger">Reject</button>
        </div>
    </x-modal>

    {{-- ─── Approve Application Modal ────────────────────────────────────────────── --}}
    <x-modal id="approve-modal" title="Approve Application" size="md">
        <p class="text-sm text-gray-600 mb-4">
            Approve <strong id="approve-vendor-name"></strong>?
            The vendor will be notified and gain access to the platform.
        </p>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate Override (%)</label>
                <input type="number" id="approve-commission" class="form-input w-full text-sm" step="0.01" min="0" max="100"
                    placeholder="Leave empty for category default"
                    value="{{ $vendor->commission_rate }}">
                <p class="text-xs text-gray-400 mt-1">Current: {{ $vendor->commission_rate ? $vendor->commission_rate . '%' : 'Category default' }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Account Manager</label>
                <select id="approve-account-manager" class="form-input w-full text-sm">
                    <option value="">— None —</option>
                    @foreach($admins as $a)
                        <option value="{{ $a->id }}" {{ $vendor->account_manager_admin_id === $a->id ? 'selected' : '' }}>
                            {{ $a->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (internal)</label>
                <textarea id="approve-notes" rows="2" class="form-input w-full text-sm" placeholder="Optional notes…"></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#approve-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-approve-btn" class="btn btn-primary">Confirm Approval</button>
        </div>
    </x-modal>

    {{-- ─── Reject Application Modal ─────────────────────────────────────────────── --}}
    <x-modal id="reject-modal" title="Reject Application" size="md">
        <p class="text-sm text-gray-600 mb-3">Reject <strong id="reject-vendor-name"></strong>. Select the rejection reasons:</p>
        <div class="space-y-2 mb-4">
            @foreach([
                'documents_incomplete'    => 'Documents Incomplete',
                'documents_invalid'       => 'Documents Invalid or Unreadable',
                'business_not_verifiable' => 'Business Cannot Be Verified',
                'policy_violation'        => 'Policy Violation',
                'duplicate_account'       => 'Duplicate Account',
                'prohibited_category'     => 'Prohibited Category',
                'other'                   => 'Other',
            ] as $code => $label)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="form-checkbox rejection-code-checkbox" value="{{ $code }}">
                    <span class="text-sm text-gray-700">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Detailed Reason <span class="text-red-500">*</span></label>
        <textarea id="reject-reason-input" rows="3" class="form-input w-full text-sm" placeholder="Explain in detail why this application is being rejected…"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="reject-reason-error">Rejection reason is required.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#reject-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-reject-btn" class="btn btn-danger">Reject Application</button>
        </div>
    </x-modal>

    {{-- ─── Request More Info Modal ──────────────────────────────────────────────── --}}
    <x-modal id="request-info-modal" title="Request More Information" size="md">
        <p class="text-sm text-gray-600 mb-3">Select which documents you need from <strong id="request-info-vendor-name"></strong>:</p>
        <div class="space-y-2 mb-4">
            @foreach([
                'business_license' => 'Business License',
                'tax_certificate'  => 'Tax Certificate',
                'owner_id'         => 'Owner ID',
                'bank_statement'   => 'Bank Statement',
                'trade_license'    => 'Trade License',
                'other'            => 'Other / Custom',
            ] as $docType => $docLabel)
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="form-checkbox request-doc-checkbox" value="{{ $docType }}">
                    <span class="text-sm text-gray-700">{{ $docLabel }}</span>
                </label>
            @endforeach
        </div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Message to vendor</label>
        <textarea id="request-info-message" rows="3" class="form-input w-full text-sm" placeholder="Additional instructions for the vendor…"></textarea>
        <p class="text-xs text-red-500 hidden mt-1" id="request-doc-error">Select at least one document type.</p>
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" class="btn btn-secondary" onclick="$('#request-info-modal').modal('close')">Cancel</button>
            <button type="button" id="confirm-request-info-btn" class="btn btn-secondary"
                data-url="{{ route('admin.vendor-applications.request-info', $vendor->id) }}">
                Send Request
            </button>
        </div>
    </x-modal>

    {{-- Data for JS --}}
    <script>
        window.vendorApp = {
            approveUrl:       @json(route('admin.vendor-applications.approve', $vendor->id)),
            rejectUrl:        @json(route('admin.vendor-applications.reject', $vendor->id)),
            requestInfoUrl:   @json(route('admin.vendor-applications.request-info', $vendor->id)),
            startReviewUrl:   @json(route('admin.vendor-applications.start-review', $vendor->id)),
            assignMeUrl:      @json(route('admin.vendor-applications.assign-me', $vendor->id)),
            canApprove:       {{ $canApprove ? 'true' : 'false' }},
        };
    </script>

@endsection
