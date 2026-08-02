@extends('layouts.admin')

@section('title', 'الإيرادات المالية — ' . __('admin.marketer_campaigns.title'))

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">الإيرادات المالية لحملات الماركتر</h1>
    <p class="text-sm text-gray-500 mt-0.5">رسوم المنصة، الكوميشن المستحق للماركترز، وصافي الربح</p>
</div>

{{-- ─── Filters ─────────────────────────────────────────────────────────────── --}}
<x-card class="mb-5">
    <form method="GET" action="{{ route('admin.marketer-campaigns.financials') }}" class="flex flex-wrap gap-3 items-end">
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">من تاريخ</label>
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input w-full text-sm">
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-gray-600 mb-1">إلى تاريخ</label>
            <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input w-full text-sm">
        </div>
        <div class="w-48">
            <label class="block text-xs font-medium text-gray-600 mb-1">الدولة</label>
            <select name="country_id" class="form-input w-full text-sm">
                <option value="">كل الدول</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" {{ (string) request('country_id') === (string) $country->id ? 'selected' : '' }}>
                        {{ $country->name_en }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">تصفية</button>
        <a href="{{ route('admin.marketer-campaigns.financials') }}" class="btn btn-ghost btn-sm">إعادة تعيين</a>
    </form>
</x-card>

{{-- ─── Summary per currency ───────────────────────────────────────────────── --}}
@forelse($summaryByCurrency as $summary)
    <x-card class="mb-4">
        <div class="text-sm font-semibold text-gray-700 mb-3">{{ $summary->currency }}</div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-gray-50 rounded-xl p-4 text-center">
                <div class="text-xs text-gray-500 mb-1">إنفلوينسر قبلوا الدعوة</div>
                <div class="text-lg font-bold text-gray-900">{{ number_format($summary->total_influencers_accepted) }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 text-center">
                <div class="text-xs text-gray-500 mb-1">إجمالي الرسوم المحصلة</div>
                <div class="text-lg font-bold text-green-700">{{ number_format($summary->collected_fees) }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 text-center">
                <div class="text-xs text-gray-500 mb-1">رسوم في انتظار التحصيل</div>
                <div class="text-lg font-bold text-orange-600">{{ number_format($summary->pending_fees) }}</div>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 text-center">
                <div class="text-xs text-gray-500 mb-1">إجمالي الرسوم المتوقعة</div>
                <div class="text-lg font-bold text-gray-900">{{ number_format($summary->total_fees_expected) }}</div>
            </div>
        </div>
    </x-card>
@empty
    <x-card class="mb-4">
        <div class="text-sm text-gray-500 text-center py-4">لا توجد حملات في هذه الفترة.</div>
    </x-card>
@endforelse

{{-- ─── Campaigns table ────────────────────────────────────────────────────── --}}
<x-card>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-500 uppercase tracking-wide border-b">
                    <th class="text-right py-2 px-3">التاجر</th>
                    <th class="text-right py-2 px-3">الدولة</th>
                    <th class="text-right py-2 px-3">إنفلوينسر</th>
                    <th class="text-right py-2 px-3">أفيلييت</th>
                    <th class="text-right py-2 px-3">الرسوم</th>
                    <th class="text-right py-2 px-3">حالة الرسوم</th>
                    <th class="text-right py-2 px-3">التحويلات</th>
                    <th class="text-right py-2 px-3">كوميشن مستحق</th>
                    <th class="text-right py-2 px-3">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($campaigns as $campaign)
                    @php
                        $accepted = $campaign->invitations->where('status', 'accepted');
                        $influencerAccepted = $accepted->filter(fn ($i) => $i->marketer?->marketer_type === 'influencer')->count();
                        $affiliateAccepted  = $accepted->filter(fn ($i) => $i->marketer?->marketer_type === 'affiliate')->count();
                        $feeExpected = $accepted->sum('platform_fee_amount');
                        $feePending  = $accepted->where('platform_fee_status', 'pending')->sum('platform_fee_amount');
                        $feePaid     = $accepted->where('platform_fee_status', 'paid')->sum('platform_fee_amount');
                    @endphp
                    <tr>
                        <td class="py-2 px-3">{{ $campaign->vendor?->store_name ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $campaign->country?->name_en ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $influencerAccepted }}</td>
                        <td class="py-2 px-3">{{ $affiliateAccepted }}</td>
                        <td class="py-2 px-3">{{ number_format($feeExpected) }} {{ $campaign->currency }}</td>
                        <td class="py-2 px-3">
                            @if($feePending > 0)
                                <span class="text-orange-500">انتظار الدفع ({{ number_format($feePending) }})</span>
                            @elseif($feeExpected > 0 && $feePaid === $feeExpected)
                                <span class="text-green-600">مدفوعة</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-2 px-3">{{ $campaign->conversions_count }}</td>
                        <td class="py-2 px-3 text-red-600">{{ number_format($campaign->total_commission_owed) }} {{ $campaign->currency }}</td>
                        <td class="py-2 px-3">
                            <a href="{{ route('admin.marketer-campaigns.show', $campaign) }}" class="text-primary-600 hover:underline">عرض</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-gray-500 py-6">لا توجد حملات.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        {{ $campaigns->links() }}
    </div>
</x-card>

@endsection
