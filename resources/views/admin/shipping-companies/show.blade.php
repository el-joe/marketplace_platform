@extends('layouts.admin')

@section('title', $shippingCompany->name)

@section('content')

{{-- ─── Header ──────────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <a href="{{ route('admin.shipping-companies.index') }}"
           class="text-sm text-indigo-600 hover:underline">← Shipping Companies</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $shippingCompany->name }}</h1>
        @if($shippingCompany->legal_name)
        <p class="text-sm text-gray-500">{{ $shippingCompany->legal_name }}</p>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @if($shippingCompany->status === 'pending')
        <form method="POST" action="{{ route('admin.shipping-companies.approve', $shippingCompany->id) }}">
            @csrf
            <button class="btn btn-success btn-sm">Approve</button>
        </form>
        @endif
        @if($shippingCompany->status !== 'suspended')
        <form method="POST" action="{{ route('admin.shipping-companies.suspend', $shippingCompany->id) }}"
              onsubmit="return confirm('Suspend this company?')">
            @csrf
            <button class="btn btn-danger btn-sm">Suspend</button>
        </form>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ─── Info Card ──────────────────────────────────────────────────────── --}}
    <div class="lg:col-span-1 space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-bold text-gray-900 mb-4">Company Details</h2>
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-gray-500 text-xs uppercase tracking-wide">Status</dt>
                    <dd class="mt-0.5">
                        @php $sc = ['active'=>'emerald','pending'=>'amber','suspended'=>'red'][$shippingCompany->status] ?? 'gray'; @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-{{ $sc }}-100 text-{{ $sc }}-700 capitalize">
                            {{ $shippingCompany->status }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 text-xs uppercase tracking-wide">Country</dt>
                    <dd class="font-medium text-gray-800">{{ $shippingCompany->country?->name_en ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 text-xs uppercase tracking-wide">Contact Email</dt>
                    <dd class="font-medium text-gray-800">{{ $shippingCompany->contact_email }}</dd>
                </div>
                @if($shippingCompany->contact_phone)
                <div>
                    <dt class="text-gray-500 text-xs uppercase tracking-wide">Phone</dt>
                    <dd class="font-medium text-gray-800">{{ $shippingCompany->contact_phone }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-gray-500 text-xs uppercase tracking-wide">Supervisor Notifications</dt>
                    <dd class="font-medium {{ $shippingCompany->can_supervisors_receive_all_notifications ? 'text-emerald-600' : 'text-gray-400' }}">
                        {{ $shippingCompany->can_supervisors_receive_all_notifications ? 'Enabled (company-level)' : 'Disabled' }}
                    </dd>
                </div>
                @if($shippingCompany->approved_at)
                <div>
                    <dt class="text-gray-500 text-xs uppercase tracking-wide">Approved At</dt>
                    <dd class="font-medium text-gray-800">{{ $shippingCompany->approved_at->format('Y-m-d H:i') }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- ─── Supervisors ─────────────────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900">Supervisors</h2>
                <p class="text-xs text-gray-500 mt-0.5">Toggle notifications per supervisor independently.</p>
            </div>
            @if($shippingCompany->supervisors->isEmpty())
            <div class="px-6 py-8 text-center text-gray-400 text-sm">No supervisors added yet.</div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold">Name</th>
                            <th class="px-6 py-3 text-left font-semibold">Email</th>
                            <th class="px-6 py-3 text-left font-semibold">Permissions</th>
                            <th class="px-6 py-3 text-left font-semibold">Active</th>
                            <th class="px-6 py-3 text-left font-semibold">Notifications</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($shippingCompany->supervisors as $sup)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $sup->name }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $sup->email }}</td>
                            <td class="px-6 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($sup->permissions ?? [] as $perm)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600">{{ $perm }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $sup->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600' }}">
                                    {{ $sup->is_active ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td class="px-6 py-3">
                                <button type="button"
                                        onclick="toggleNotifications('{{ $sup->id }}', this)"
                                        data-url="{{ route('admin.shipping-companies.supervisors.toggle-notifications', $sup->id) }}"
                                        class="text-xs font-semibold px-3 py-1 rounded-full transition
                                            {{ $sup->receives_all_notifications
                                                ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                                                : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                    {{ $sup->receives_all_notifications ? 'On' : 'Off' }}
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- ─── Recent Agents ───────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-900">Agents (latest 20)</h2>
            </div>
            @if($shippingCompany->agents->isEmpty())
            <div class="px-6 py-8 text-center text-gray-400 text-sm">No agents added yet.</div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold">Name</th>
                            <th class="px-6 py-3 text-left font-semibold">Phone</th>
                            <th class="px-6 py-3 text-left font-semibold">Status</th>
                            <th class="px-6 py-3 text-left font-semibold">Rating</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($shippingCompany->agents as $agent)
                        @php $ac = ['active'=>'emerald','suspended'=>'red','inactive'=>'gray','on_shift'=>'blue'][$agent->status] ?? 'gray'; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $agent->name }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $agent->phone }}</td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $ac }}-100 text-{{ $ac }}-700 capitalize">
                                    {{ $agent->status }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ number_format($agent->rating_avg, 1) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleNotifications(supervisorId, btn) {
    const url = btn.dataset.url;
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.textContent = data.enabled ? 'On' : 'Off';
            btn.className = btn.className.replace(/bg-\w+-100 text-\w+-\w+/g, '');
            if (data.enabled) {
                btn.classList.add('bg-emerald-100', 'text-emerald-700', 'hover:bg-emerald-200');
                btn.classList.remove('bg-gray-100', 'text-gray-500', 'hover:bg-gray-200');
            } else {
                btn.classList.add('bg-gray-100', 'text-gray-500', 'hover:bg-gray-200');
                btn.classList.remove('bg-emerald-100', 'text-emerald-700', 'hover:bg-emerald-200');
            }
        }
    });
}
</script>
@endpush

@endsection
