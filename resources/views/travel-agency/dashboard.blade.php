@extends('layouts.travel-agency')

@section('title', 'لوحة التحكم')

@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-black text-gray-900">مرحباً، {{ $agency->name }}</h1>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        @php
        $statuses = [
            'draft'          => ['label' => 'مسودة',        'color' => 'bg-gray-100 text-gray-700'],
            'pending_review' => ['label' => 'قيد المراجعة', 'color' => 'bg-amber-100 text-amber-700'],
            'active'         => ['label' => 'نشطة',         'color' => 'bg-emerald-100 text-emerald-700'],
            'sold_out'       => ['label' => 'مكتملة',       'color' => 'bg-purple-100 text-purple-700'],
            'expired'        => ['label' => 'منتهية',       'color' => 'bg-gray-100 text-gray-500'],
        ];
        @endphp
        @foreach($statuses as $key => $meta)
        <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
            <p class="text-3xl font-black text-gray-900">{{ $counts[$key] ?? 0 }}</p>
            <span class="mt-1 inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $meta['color'] }}">
                {{ $meta['label'] }}
            </span>
        </div>
        @endforeach
    </div>

    {{-- Recent packages --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-900">آخر الباقات</h2>
            <a href="{{ route('travel-agency.packages.create') }}"
               class="px-4 py-2 bg-blue-500 text-white rounded-lg text-sm font-medium hover:bg-blue-400">
                + إضافة باقة
            </a>
        </div>
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الباقة</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الوجهة</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">موعد السفر</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">السعر</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الحالة</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($recentPackages as $pkg)
                @php
                $colors = ['draft'=>'bg-gray-100 text-gray-600','pending_review'=>'bg-amber-100 text-amber-700','active'=>'bg-emerald-100 text-emerald-700','sold_out'=>'bg-purple-100 text-purple-700','expired'=>'bg-gray-100 text-gray-500'];
                $labels = ['draft'=>'مسودة','pending_review'=>'قيد المراجعة','active'=>'نشطة','sold_out'=>'مكتملة','expired'=>'منتهية'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $pkg->title_ar ?: $pkg->title_en }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->destination_country }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->departure_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $pkg->priceFormatted() }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$pkg->status] ?? '' }}">
                            {{ $labels[$pkg->status] ?? $pkg->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('travel-agency.packages.show', $pkg) }}" class="text-blue-600 text-xs hover:underline">عرض</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400 text-sm">لا توجد باقات بعد. <a href="{{ route('travel-agency.packages.create') }}" class="text-blue-600 hover:underline">أضف أول باقة</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
