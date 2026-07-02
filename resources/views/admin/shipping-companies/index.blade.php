@extends('layouts.admin')

@section('title', __('admin.shipping_section.title'))

@section('content')

{{-- ─── Page Header ─────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.shipping_section.shipping_companies') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.shipping_section.companies_desc') }}</p>
    </div>
    <a href="{{ route('admin.shipping-companies.fallback-rules.index') }}"
       class="btn btn-secondary btn-sm">
        {{ __('admin.shipping_section.fallback_rules') }}
    </a>
</div>

{{-- ─── Stats ───────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label' => __('admin.shipping_section.stat_total'),     'value' => $stats['total'],     'color' => 'gray'],
        ['label' => __('admin.shipping_section.stat_active'),    'value' => $stats['active'],    'color' => 'success'],
        ['label' => __('admin.shipping_section.stat_pending'),   'value' => $stats['pending'],   'color' => 'warning'],
        ['label' => __('admin.shipping_section.stat_suspended'), 'value' => $stats['suspended'], 'color' => 'danger'],
    ] as $stat)
    <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
        <div class="text-2xl font-black text-{{ $stat['color'] === 'gray' ? 'gray-700' : ($stat['color'] === 'success' ? 'emerald-600' : ($stat['color'] === 'warning' ? 'amber-600' : 'red-600')) }}">
            {{ $stat['value'] }}
        </div>
        <div class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- ─── Table ───────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-start font-semibold">{{ __('admin.shipping_section.company_col') }}</th>
                    <th class="px-6 py-3 text-start font-semibold">{{ __('admin.shipping_section.country_col') }}</th>
                    <th class="px-6 py-3 text-start font-semibold">{{ __('admin.shipping_section.contact_col') }}</th>
                    <th class="px-6 py-3 text-start font-semibold">{{ __('admin.shipping_section.supervisors_col') }}</th>
                    <th class="px-6 py-3 text-start font-semibold">{{ __('admin.shipping_section.agents_col') }}</th>
                    <th class="px-6 py-3 text-start font-semibold">{{ __('admin.shipping_section.status_col') }}</th>
                    <th class="px-6 py-3 text-start font-semibold">{{ __('admin.shipping_section.actions_col') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($companies as $company)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="font-semibold text-gray-900">{{ $company->name }}</div>
                        @if($company->legal_name)
                        <div class="text-xs text-gray-400">{{ $company->legal_name }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $company->country?->name_en ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <div class="text-gray-700">{{ $company->contact_email }}</div>
                        @if($company->contact_phone)
                        <div class="text-xs text-gray-400">{{ $company->contact_phone }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center font-semibold text-gray-700">{{ $company->supervisors_count }}</td>
                    <td class="px-6 py-4 text-center font-semibold text-gray-700">{{ $company->agents_count }}</td>
                    <td class="px-6 py-4">
                        @php
                            $sc = ['active'=>'emerald','pending'=>'amber','suspended'=>'red'][$company->status] ?? 'gray';
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-{{ $sc }}-100 text-{{ $sc }}-700 capitalize">
                            {{ $company->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.shipping-companies.show', $company->id) }}"
                               class="text-indigo-600 hover:underline text-xs font-medium">{{ __('admin.shipping_section.view') }}</a>
                            @if($company->status === 'pending')
                            <form method="POST" action="{{ route('admin.shipping-companies.approve', $company->id) }}">
                                @csrf
                                <button class="text-emerald-600 hover:underline text-xs font-medium">{{ __('admin.shipping_section.approve') }}</button>
                            </form>
                            @endif
                            @if($company->status !== 'suspended')
                            <form method="POST" action="{{ route('admin.shipping-companies.suspend', $company->id) }}"
                                  onsubmit="return confirm('{{ __('admin.shipping_section.suspend_confirm') }}')">
                                @csrf
                                <button class="text-red-500 hover:underline text-xs font-medium">{{ __('admin.shipping_section.suspend') }}</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">{{ __('admin.no_shipping_companies') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($companies->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $companies->links() }}
    </div>
    @endif
</div>

@endsection
