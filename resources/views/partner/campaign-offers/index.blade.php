@extends('layouts.partner')

@section('title', 'عروض الحملات التسويقية')
@section('page-title', 'عروض الحملات التسويقية')

@section('content')
<div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">عروض الحملات التسويقية</h1>
            <p class="mt-1 text-sm text-gray-500">أنشئ عروضاً للمسوّقين وادعوهم للترويج لمنتجاتك بعمولة.</p>
        </div>
        <a href="{{ route('partner.campaign-offers.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            عرض جديد
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
        @foreach ([
            ['label' => 'مسودة',           'value' => $stats['draft'],       'cls' => 'text-gray-700'],
            ['label' => 'نشطة',            'value' => $stats['active'],      'cls' => 'text-emerald-600'],
            ['label' => 'إجمالي الدعوات',  'value' => $stats['invited'],     'cls' => 'text-blue-600'],
            ['label' => 'قبلوا',            'value' => $stats['accepted'],    'cls' => 'text-indigo-600'],
            ['label' => 'تحويلات',          'value' => $stats['conversions'], 'cls' => 'text-amber-600'],
        ] as $s)
            <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 text-center shadow-sm">
                <p class="text-2xl font-bold {{ $s['cls'] }}">{{ number_format($s['value']) }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $s['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Offers list --}}
    @if ($offers->isEmpty())
        <div class="rounded-xl border-2 border-dashed border-gray-200 py-16 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 1 8.835-2.535m0 0A23.74 23.74 0 0 1 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46"/>
            </svg>
            <p class="mt-4 text-sm text-gray-500">لا توجد عروض بعد.</p>
            <a href="{{ route('partner.campaign-offers.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700">
                أنشئ أول عرض
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">العرض</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">النوع</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">العمولة</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الفترة</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الحالة</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600">الدعوات</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($offers as $offer)
                        @php
                            $statusMap = [
                                'draft'         => ['label' => 'مسودة',            'cls' => 'bg-gray-100 text-gray-600'],
                                'pending_admin' => ['label' => 'بانتظار المراجعة', 'cls' => 'bg-amber-100 text-amber-700'],
                                'active'        => ['label' => 'نشط',              'cls' => 'bg-emerald-100 text-emerald-700'],
                                'paused'        => ['label' => 'موقوف',            'cls' => 'bg-blue-100 text-blue-700'],
                                'rejected'      => ['label' => 'مرفوض',            'cls' => 'bg-red-100 text-red-700'],
                                'ended'         => ['label' => 'منتهي',            'cls' => 'bg-gray-100 text-gray-400'],
                            ];
                            $st = $statusMap[$offer->status] ?? ['label' => $offer->status, 'cls' => 'bg-gray-100 text-gray-600'];
                            $typeMap = [
                                'product_promotion' => 'ترويج منتج',
                                'store_promotion'   => 'ترويج متجر',
                                'brand_deal'        => 'اتفاقية براند',
                                'product_specific'  => 'منتج محدد',
                                'flash_sale'        => 'تخفيض سريع',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('partner.campaign-offers.show', $offer->id) }}"
                                   class="font-medium text-primary-600 hover:underline">{{ $offer->name }}</a>
                                <p class="text-xs text-gray-400 mt-0.5">{{ $offer->created_at->format('d M Y') }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $typeMap[$offer->campaign_type] ?? $offer->campaign_type }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-gray-900">{{ $offer->offered_commission_rate }}%</span>
                                <span class="text-xs text-gray-400 mr-1">{{ $offer->commission_type === 'percentage' ? 'من المبيعات' : ($offer->commission_type === 'flat_per_order' ? 'لكل طلب' : 'لكل نقرة') }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">
                                {{ $offer->starts_at->format('d M') }} – {{ $offer->ends_at->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $st['cls'] }}">
                                    {{ $st['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $offer->invitations_count }}
                                @if ($offer->accepted_count > 0)
                                    <span class="text-xs text-emerald-600">({{ $offer->accepted_count }} قبلوا)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-left">
                                <a href="{{ route('partner.campaign-offers.show', $offer->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    عرض
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
