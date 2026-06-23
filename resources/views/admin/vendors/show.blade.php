@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', $vendor->store_name . ' — Vendor')

@section('content')

@php
    $statusColors = [
        'pending'      => 'gray',
        'active'       => 'success',
        'suspended'    => 'warning',
        'rejected'     => 'danger',
        'blacklisted'  => 'danger',
        'under_review' => 'primary',
    ];
    $docStatusColors = [
        'pending'  => 'gray',
        'approved' => 'success',
        'verified' => 'success',
        'rejected' => 'danger',
        'expired'  => 'danger',
    ];
    $strikeColors = [
        'warning'  => 'gray',
        'minor'    => 'warning',
        'major'    => 'warning',
        'critical' => 'danger',
    ];
    $activeStrikesCount = $vendor->strikes->where('is_active', true)->count();
@endphp

{{-- ─── Page header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-start justify-between gap-4">
    <div class="flex items-center gap-4 min-w-0">
        @if($vendor->avatar)
            <img src="{{ $vendor->avatar }}" class="w-14 h-14 rounded-xl object-cover flex-shrink-0 border border-gray-200">
        @else
            <div class="w-14 h-14 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                {{ strtoupper(substr($vendor->store_name, 0, 1)) }}
            </div>
        @endif
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 truncate">{{ $vendor->store_name }}</h1>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
                <x-badge :color="$statusColors[$vendor->global_status] ?? 'gray'">
                    {{ ucwords(str_replace('_', ' ', $vendor->global_status)) }}
                </x-badge>
                @if($vendor->payout_hold_active)
                    <x-badge color="warning">Payout Hold</x-badge>
                @endif
                @if($activeStrikesCount > 0)
                    <x-badge color="danger">{{ $activeStrikesCount }} Active Strike(s)</x-badge>
                @endif
            </div>
        </div>
    </div>
    <a href="{{ route('admin.vendors.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex-shrink-0 mt-1">← Back</a>
</div>

{{-- ─── Two-column layout ───────────────────────────────────────────────────── --}}
<div class="grid grid-cols-12 gap-6">

    {{-- ══ MAIN (8/12) ════════════════════════════════════════════════════════ --}}
    <div class="col-span-12 lg:col-span-8 space-y-6">

        <div x-data="{ tab: 'profile' }">
            {{-- Tab bar --}}
            <div class="flex gap-1 border-b border-gray-200 overflow-x-auto pb-px mb-6">
                @foreach([
                    'profile'     => 'Profile',
                    'documents'   => 'Documents',
                    'bank'        => 'Bank Accounts',
                    'strikes'     => 'Strikes',
                    'performance' => 'Performance',
                    'orders'      => 'Orders',
                    'payouts'     => 'Payouts',
                    'activity'    => 'Activity Log',
                ] as $key => $label)
                    <button type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-b-2 border-primary-600 text-primary-700 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 text-sm whitespace-nowrap transition-colors focus:outline-none">
                        {{ $label }}
                        @if($key === 'strikes' && $activeStrikesCount > 0)
                            <span class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full bg-danger-500 text-white text-xs">{{ $activeStrikesCount }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- ── Profile ──────────────────────────────────────────────────── --}}
            <div x-show="tab === 'profile'">
                <x-card title="Vendor Profile">
                    <x-slot name="actions">
                        <button type="button" id="edit-profile-btn" class="btn btn-secondary btn-sm text-xs">Edit</button>
                    </x-slot>

                    <div id="profile-view" class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        @foreach([
                            'Store Name'      => $vendor->store_name,
                            'Store Slug'      => $vendor->store_slug,
                            'Business Name'   => $vendor->business_name ?: '—',
                            'Business Type'   => ucfirst(str_replace('_', ' ', $vendor->business_type ?? '—')),
                            'Reg. Number'     => $vendor->business_registration_number ?: '—',
                            'Tax ID'          => $vendor->tax_id ?: '—',
                            'Contact Email'   => $vendor->contact_email ?: '—',
                            'Contact Phone'   => $vendor->contact_phone ?: '—',
                            'WhatsApp'        => $vendor->whatsapp_number ?: '—',
                            'Commission Rate' => $vendor->commission_rate ? $vendor->commission_rate . '%' : 'Platform default',
                            'Payout Schedule' => ucfirst($vendor->payout_schedule ?? '—'),
                            'Country'         => $vendor->country?->name_en ?? '—',
                            'Approved At'     => $vendor->approved_at?->format('d M Y') ?? '—',
                            'Approved By'     => $vendor->approvedByAdmin?->name ?? '—',
                            'Joined'          => $vendor->created_at->format('d M Y'),
                        ] as $label => $value)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</dt>
                                <dd class="mt-0.5 font-medium text-gray-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                        @if($vendor->store_description)
                            <div class="col-span-2">
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Description</dt>
                                <dd class="mt-0.5 text-gray-700">{{ $vendor->store_description }}</dd>
                            </div>
                        @endif
                    </div>

                    <form id="profile-edit-form" class="hidden" data-vendor-id="{{ $vendor->id }}">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <x-form.input name="store_name"    label="Store Name"    :value="$vendor->store_name"    required />
                            <x-form.input name="store_slug"    label="Store Slug"    :value="$vendor->store_slug"    required />
                            <x-form.input name="business_name" label="Business Name" :value="$vendor->business_name" />
                            <x-form.select name="business_type" label="Business Type" :value="$vendor->business_type"
                                :options="['individual' => 'Individual', 'sole_prop' => 'Sole Proprietor', 'llc' => 'LLC', 'corp' => 'Corporation']"/>
                            <x-form.input name="contact_email"   label="Contact Email" :value="$vendor->contact_email"   type="email"/>
                            <x-form.input name="contact_phone"   label="Contact Phone" :value="$vendor->contact_phone" />
                            <x-form.input name="commission_rate" label="Commission Rate (%)" :value="$vendor->commission_rate" type="number" step="0.01"/>
                            <x-form.select name="payout_schedule" label="Payout Schedule" :value="$vendor->payout_schedule"
                                :options="['weekly' => 'Weekly', 'biweekly' => 'Bi-weekly', 'monthly' => 'Monthly']"/>
                            <div class="col-span-2">
                                <x-form.textarea name="store_description" label="Store Description" :value="$vendor->store_description" rows="3"/>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Save changes</button>
                            <button type="button" id="cancel-edit-btn" class="btn btn-ghost btn-sm">Cancel</button>
                        </div>
                    </form>
                </x-card>
            </div>

            {{-- ── Documents ────────────────────────────────────────────────── --}}
            <div x-show="tab === 'documents'">
                <x-card title="Documents">
                    <x-slot name="actions">
                        <button type="button" data-open-modal="request-doc-modal" class="btn btn-secondary btn-sm text-xs">Request Document</button>
                    </x-slot>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Expires</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Verified By</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($vendor->documents as $doc)
                                    <tr data-doc-id="{{ $doc->id }}" class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 font-medium text-gray-900">
                                            {{ ucwords(str_replace('_', ' ', $doc->document_type)) }}
                                        </td>
                                        <td class="py-3 pr-4">
                                            <x-badge :color="$docStatusColors[$doc->status] ?? 'gray'">{{ ucfirst($doc->status) }}</x-badge>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $doc->expires_at?->format('d M Y') ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $doc->verifiedByAdmin?->name ?? '—' }}</td>
                                        <td class="py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($doc->file_path)
                                                    <a href="{{ $doc->full_file_path }}" target="_blank" class="text-xs text-primary-600 hover:underline">View</a>
                                                @endif
                                                @if(!in_array($doc->status, ['approved', 'verified']))
                                                    <button type="button" class="text-xs text-success-700 hover:underline" data-verify-doc="{{ $doc->id }}">Verify</button>
                                                @endif
                                                @if($doc->status !== 'rejected')
                                                    <button type="button" class="text-xs text-danger-600 hover:underline" data-reject-doc="{{ $doc->id }}">Reject</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-sm text-gray-400">No documents uploaded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ── Bank Accounts ─────────────────────────────────────────────── --}}
            <div x-show="tab === 'bank'">
                <x-card title="Bank Accounts">
                    @if($vendor->bankAccounts->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-6">No bank accounts added.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-left">
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Bank</th>
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">IBAN (last 4)</th>
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Currency</th>
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Primary</th>
                                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="py-2 text-right text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($vendor->bankAccounts as $account)
                                        <tr class="hover:bg-gray-50/50">
                                            <td class="py-3 pr-4 font-medium">{{ $account->bank_name }}</td>
                                            <td class="py-3 pr-4 font-mono text-gray-600">•••• {{ $account->iban ? substr($account->iban, -4) : '——' }}</td>
                                            <td class="py-3 pr-4 text-gray-600">{{ strtoupper($account->currency) }}</td>
                                            <td class="py-3 pr-4">
                                                @if($account->is_primary)
                                                    <x-badge color="success">Primary</x-badge>
                                                @else
                                                    <span class="text-gray-400 text-xs">—</span>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4">
                                                <x-badge :color="$account->verification_status === 'verified' ? 'success' : ($account->verification_status === 'rejected' ? 'danger' : 'gray')">
                                                    {{ ucfirst($account->verification_status) }}
                                                </x-badge>
                                            </td>
                                            <td class="py-3 text-right">
                                                @if($account->verification_status !== 'verified')
                                                    <button type="button"
                                                            class="text-xs text-success-700 hover:underline"
                                                            data-verify-bank="{{ $account->id }}"
                                                            data-vendor-id="{{ $vendor->id }}">Verify</button>
                                                @else
                                                    <span class="text-xs text-gray-400">Verified</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- ── Strikes ───────────────────────────────────────────────────── --}}
            <div x-show="tab === 'strikes'">
                <x-card title="Strikes">
                    <x-slot name="actions">
                        <button type="button" data-open-modal="issue-strike-modal" class="btn btn-danger btn-sm text-xs">Issue Strike</button>
                    </x-slot>

                    <div class="grid grid-cols-2 gap-4 mb-5">
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-center">
                            <div class="text-2xl font-bold text-danger-600" id="active-strikes-count">{{ $activeStrikesCount }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">Active Strikes</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-center">
                            <div class="text-2xl font-bold text-gray-700">{{ $vendor->strikes->count() }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">Total Strikes</div>
                        </div>
                    </div>

                    @if($activeStrikesCount >= 2)
                        <div class="mb-4 rounded-lg bg-warning-50 border border-warning-200 px-4 py-3 text-sm text-warning-800 flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Warning: {{ $activeStrikesCount }} active strikes. One more critical or total of 3 will auto-suspend this vendor.
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Reason</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Severity</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Issued By</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Expires</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">Active</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($vendor->strikes->sortByDesc('created_at') as $strike)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 text-gray-600 whitespace-nowrap">{{ $strike->created_at->format('d M Y') }}</td>
                                        <td class="py-3 pr-4 text-gray-900 max-w-xs">
                                            <div class="font-medium">{{ ucwords(str_replace('_', ' ', $strike->reason)) }}</div>
                                            @if($strike->description)
                                                <div class="text-xs text-gray-500 truncate">{{ $strike->description }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 pr-4">
                                            <x-badge :color="$strikeColors[$strike->severity] ?? 'gray'">{{ ucfirst($strike->severity) }}</x-badge>
                                        </td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $strike->issuedByAdmin?->name ?? '—' }}</td>
                                        <td class="py-3 pr-4 text-gray-600">{{ $strike->expires_at?->format('d M Y') ?? 'Never' }}</td>
                                        <td class="py-3">
                                            @if($strike->is_active)
                                                <x-badge color="danger">Active</x-badge>
                                            @else
                                                <x-badge color="gray">Expired</x-badge>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-8 text-center text-sm text-gray-400">No strikes on record.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ── Performance ──────────────────────────────────────────────── --}}
            <div x-show="tab === 'performance'" id="performance-tab">
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                    <x-stat-card title="Total GMV"    :value="'$' . number_format($vendor->total_sales, 2)"    icon="currency-dollar" icon-bg="bg-success-100 text-success-600"/>
                    <x-stat-card title="Total Orders" :value="number_format($vendor->total_orders)"             icon="shopping-bag"    icon-bg="bg-primary-100 text-primary-600"/>
                    <x-stat-card title="Avg Rating"   :value="number_format($vendor->store_rating_avg, 1) . ' / 5'" icon="star"       icon-bg="bg-warning-100 text-warning-600"/>
                    <x-stat-card title="Return Rate"  :value="$vendor->return_rate_pct . '%'"                   icon="arrow-uturn-left" icon-bg="bg-danger-100 text-danger-600"/>
                </div>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <x-card title="GMV — Last 30 Days"><div class="h-56"><canvas id="gmv-chart"></canvas></div></x-card>
                    <x-card title="Orders by Status"><div class="h-56"><canvas id="orders-status-chart"></canvas></div></x-card>
                </div>
                <x-card title="vs. Platform Average" class="mt-6">
                    <div class="grid grid-cols-3 gap-6 text-sm text-center">
                        <div>
                            <div class="text-xs text-gray-500 mb-1">GMV</div>
                            <div class="text-lg font-bold text-gray-900">${{ number_format($vendor->total_sales, 0) }}</div>
                            <div class="text-xs text-gray-400">Avg: <span id="avg-gmv">—</span></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Orders</div>
                            <div class="text-lg font-bold text-gray-900">{{ number_format($vendor->total_orders) }}</div>
                            <div class="text-xs text-gray-400">Avg: <span id="avg-orders">—</span></div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 mb-1">Rating</div>
                            <div class="text-lg font-bold text-gray-900">{{ number_format($vendor->store_rating_avg, 1) }}</div>
                            <div class="text-xs text-gray-400">Avg: <span id="avg-rating">—</span></div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- ── Orders ────────────────────────────────────────────────────── --}}
            <div x-show="tab === 'orders'">
                <x-card title="Order History">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Sub-Order</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Order #</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Total</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($subOrders as $so)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 font-mono text-xs text-gray-700">{{ $so->sub_order_number }}</td>
                                        <td class="py-3 pr-4 text-primary-600">
                                            <a href="{{ route('admin.orders.show', $so->id) }}" class="hover:underline">{{ $so->order_number }}</a>
                                        </td>
                                        <td class="py-3 pr-4 tabular-nums">${{ number_format($so->vendor_payout / 100, 2) }}</td>
                                        <td class="py-3 pr-4">
                                            <x-badge color="gray">{{ ucwords(str_replace('_', ' ', $so->status)) }}</x-badge>
                                        </td>
                                        <td class="py-3 text-xs text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($so->created_at)->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-8 text-center text-sm text-gray-400">No orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ── Payouts ───────────────────────────────────────────────────── --}}
            <div x-show="tab === 'payouts'">
                <x-card title="Payout History">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 text-left">
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Payout #</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Period</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Gross</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Net</th>
                                    <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="py-2 text-xs font-medium text-gray-500 uppercase">Processed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($payouts as $payout)
                                    @php
                                        $pc = match($payout->status) {
                                            'completed','paid' => 'success',
                                            'pending','processing' => 'gray',
                                            'failed' => 'danger',
                                            'on_hold' => 'warning',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-3 pr-4 font-mono text-xs">{{ $payout->payout_number }}</td>
                                        <td class="py-3 pr-4 text-xs text-gray-600 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($payout->period_start)->format('d M') }} – {{ \Carbon\Carbon::parse($payout->period_end)->format('d M Y') }}
                                        </td>
                                        <td class="py-3 pr-4 tabular-nums">${{ number_format($payout->gross_sales / 100, 2) }}</td>
                                        <td class="py-3 pr-4 tabular-nums font-medium">${{ number_format($payout->net_amount / 100, 2) }}</td>
                                        <td class="py-3 pr-4"><x-badge :color="$pc">{{ ucfirst($payout->status) }}</x-badge></td>
                                        <td class="py-3 text-xs text-gray-500">{{ $payout->processed_at ? \Carbon\Carbon::parse($payout->processed_at)->format('d M Y') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="py-8 text-center text-sm text-gray-400">No payouts yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

            {{-- ── Activity Log ──────────────────────────────────────────────── --}}
            <div x-show="tab === 'activity'">
                <x-card title="Activity Log">
                    @if($activityLog->isEmpty())
                        <p class="text-sm text-gray-400 text-center py-6">No activity recorded.</p>
                    @else
                        <div class="space-y-3 text-sm">
                            @foreach($activityLog as $entry)
                                @php
                                    $props = is_string($entry->properties) ? json_decode($entry->properties, true) : (array) $entry->properties;
                                    $attrs = $props['attributes'] ?? [];
                                    $old   = $props['old'] ?? [];
                                @endphp
                                <div class="flex gap-3">
                                    <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">A</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-medium text-gray-900">{{ $entry->causer_id ? 'Admin' : 'System' }}</span>
                                            <span class="text-gray-500">{{ ucwords(str_replace('_', ' ', $entry->description)) }}</span>
                                            <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($entry->created_at)->diffForHumans() }}</span>
                                        </div>
                                        @foreach($attrs as $field => $newVal)
                                            @if(isset($old[$field]))
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    <span class="font-mono bg-gray-100 px-1 rounded">{{ $field }}</span>:
                                                    <span class="line-through text-danger-600">{{ $old[$field] }}</span>
                                                    → <span class="text-success-700">{{ $newVal }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

        </div>{{-- end x-data --}}
    </div>{{-- end main --}}

    {{-- ══ SIDEBAR (4/12) ════════════════════════════════════════════════════ --}}
    <div class="col-span-12 lg:col-span-4 space-y-4">

        <x-card title="Vendor Info">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Store Name</dt>
                    <dd class="font-medium text-primary-600 truncate ml-2">{{ $vendor->store_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Slug</dt>
                    <dd class="font-mono text-xs text-gray-700">{{ $vendor->store_slug }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Account Manager</dt>
                    <dd class="font-medium text-gray-900 truncate ml-2">{{ $vendor->accountManagerAdmin?->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Rating</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($vendor->store_rating_avg, 1) }} ★ ({{ number_format($vendor->store_rating_count) }})</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Total GMV</dt>
                    <dd class="font-medium text-gray-900">${{ number_format($vendor->total_sales, 2) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Payout Hold</dt>
                    <dd>
                        @if($vendor->payout_hold_active)
                            <x-badge color="warning">Active</x-badge>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <button type="button" data-open-modal="assign-manager-modal" class="w-full btn btn-ghost btn-sm text-xs">
                    Assign Manager
                </button>
            </div>
        </x-card>

        <x-card title="Quick Actions">
            <div class="space-y-2">
                @if($vendor->global_status === 'active')
                    <button type="button" data-open-modal="suspend-modal" class="w-full btn btn-warning btn-sm">Suspend Vendor</button>
                @elseif($vendor->global_status === 'suspended')
                    <button type="button" data-reactivate-vendor="{{ $vendor->id }}" class="w-full btn btn-success btn-sm">Reactivate Vendor</button>
                @elseif($vendor->global_status === 'pending')
                    <button type="button" data-approve-vendor="{{ $vendor->id }}" class="w-full btn btn-success btn-sm">Approve Vendor</button>
                    <button type="button" data-open-modal="reject-modal" class="w-full btn btn-ghost btn-sm text-danger-600">Reject Application</button>
                @endif

                @if($vendor->payout_hold_active)
                    <button type="button" data-release-hold="{{ $vendor->id }}" class="w-full btn btn-secondary btn-sm">Release Payout Hold</button>
                @else
                    <button type="button" data-open-modal="place-hold-modal" class="w-full btn btn-ghost btn-sm">Place Payout Hold</button>
                @endif

                @if($vendor->global_status !== 'blacklisted')
                    <button type="button" data-open-modal="blacklist-modal" class="w-full btn btn-danger btn-sm">Blacklist Vendor</button>
                @endif

                <button type="button" data-open-modal="send-notification-modal" class="w-full btn btn-ghost btn-sm">Send Notification</button>
            </div>
        </x-card>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     MODALS
═══════════════════════════════════════════════════════════════════════════ --}}

{{-- Issue Strike --}}
<x-modal id="issue-strike-modal" title="Issue Strike" size="md">
    <form id="issue-strike-form">
        @csrf
        <input type="hidden" id="strike-vendor-id" value="{{ $vendor->id }}">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-danger-500">*</span></label>
                <input type="text" name="reason" class="form-input w-full" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Severity <span class="text-danger-500">*</span></label>
                <select name="severity" class="form-input w-full" required>
                    <option value="">— Select —</option>
                    <option value="warning">Warning</option>
                    <option value="minor">Minor</option>
                    <option value="major">Major</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div id="strike-warning" class="hidden rounded-lg bg-danger-50 border border-danger-200 px-3 py-2 text-sm text-danger-700"></div>
            <div id="strike-expiry-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">Expires At</label>
                <input type="date" name="expires_at" class="form-input w-full" min="{{ now()->addDay()->toDateString() }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" class="form-input w-full resize-none" rows="3"></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="submit" id="issue-strike-btn" class="btn btn-danger btn-sm">Issue Strike</button>
        </x-slot>
    </form>
</x-modal>

{{-- Place Payout Hold --}}
<x-modal id="place-hold-modal" title="Place Payout Hold" size="sm">
    <form id="hold-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-danger-500">*</span></label>
            <textarea name="reason" class="form-input w-full resize-none" rows="3" required></textarea>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="submit" class="btn btn-warning btn-sm">Place Hold</button>
        </x-slot>
    </form>
</x-modal>

{{-- Suspend --}}
<x-modal id="suspend-modal" title="Suspend Vendor" size="sm">
    <form id="suspend-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div class="space-y-4">
            <p class="text-sm text-gray-600">Suspending <strong>{{ $vendor->store_name }}</strong> will deactivate their account immediately.</p>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-danger-500">*</span></label>
                <textarea name="reason" class="form-input w-full resize-none" rows="3" required></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="submit" class="btn btn-warning btn-sm">Confirm Suspension</button>
        </x-slot>
    </form>
</x-modal>

{{-- Reject Application --}}
<x-modal id="reject-modal" title="Reject Application" size="sm">
    <form id="reject-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-danger-500">*</span></label>
            <textarea name="reason" class="form-input w-full resize-none" rows="3" required></textarea>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
        </x-slot>
    </form>
</x-modal>

{{-- Blacklist --}}
<x-modal id="blacklist-modal" title="Blacklist Vendor" size="sm">
    <form id="blacklist-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div class="rounded-lg bg-danger-50 border border-danger-200 px-4 py-3 text-sm text-danger-800 mb-4">
            Warning: This action is permanent. The vendor will be blacklisted from the platform.
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-danger-500">*</span></label>
            <textarea name="reason" class="form-input w-full resize-none" rows="3" required></textarea>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm">Confirm Blacklist</button>
        </x-slot>
    </form>
</x-modal>

{{-- Assign Manager --}}
<x-modal id="assign-manager-modal" title="Assign Account Manager" size="sm">
    <form id="assign-manager-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Admin <span class="text-danger-500">*</span></label>
            <select name="admin_id" class="form-input w-full" required>
                <option value="">— Select admin —</option>
                @foreach($admins as $admin)
                    <option value="{{ $admin->id }}" {{ $vendor->account_manager_admin_id === $admin->id ? 'selected' : '' }}>
                        {{ $admin->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Assign</button>
        </x-slot>
    </form>
</x-modal>

{{-- Reject Document --}}
<x-modal id="reject-document-modal" title="Reject Document" size="sm">
    <form id="reject-doc-form">
        @csrf
        <input type="hidden" id="reject-doc-id">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason <span class="text-danger-500">*</span></label>
                <select name="rejection_reason" class="form-input w-full" required>
                    <option value="">— Select —</option>
                    <option value="document_expired">Document expired</option>
                    <option value="poor_quality">Poor image quality</option>
                    <option value="incorrect_document">Incorrect document type</option>
                    <option value="information_mismatch">Information mismatch</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" class="form-input w-full resize-none" rows="2"></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="submit" class="btn btn-danger btn-sm">Reject Document</button>
        </x-slot>
    </form>
</x-modal>

{{-- Send Notification --}}
<x-modal id="send-notification-modal" title="Send Notification" size="md">
    <form id="send-notification-form" data-vendor-id="{{ $vendor->id }}">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-danger-500">*</span></label>
                <input type="text" name="subject" class="form-input w-full" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Message <span class="text-danger-500">*</span></label>
                <textarea name="message" class="form-input w-full resize-none" rows="5" required></textarea>
            </div>
        </div>
        <x-slot name="footer">
            <button type="button" data-modal-close class="btn btn-ghost btn-sm">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm">Send</button>
        </x-slot>
    </form>
</x-modal>

@endsection

@push('scripts')
    @vite(['resources/js/admin/vendors.js'])
    <script>
        window.VENDOR_ID   = '{{ $vendor->id }}';
        window.VENDOR_NAME = @json($vendor->store_name);
    </script>
@endpush
