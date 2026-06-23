@extends('layouts.travel-agency')

@section('title', 'باقاتي')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-gray-900">الباقات السياحية</h1>
        <a href="{{ route('travel-agency.packages.create') }}"
           class="px-5 py-2.5 bg-blue-500 text-white rounded-xl font-bold hover:bg-blue-400 transition-colors">
            + إضافة باقة جديدة
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الباقة</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الوجهة</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">موعد السفر</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">السعر</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">المقاعد</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الحالة</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($packages as $pkg)
                @php
                $colors = ['draft'=>'bg-gray-100 text-gray-600','pending_review'=>'bg-amber-100 text-amber-700','active'=>'bg-emerald-100 text-emerald-700','sold_out'=>'bg-purple-100 text-purple-700','expired'=>'bg-gray-100 text-gray-500'];
                $labels = ['draft'=>'مسودة','pending_review'=>'قيد المراجعة','active'=>'نشطة','sold_out'=>'مكتملة','expired'=>'منتهية'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900">{{ $pkg->title_ar ?: $pkg->title_en }}</p>
                        <p class="text-xs text-gray-400">{{ $pkg->title_en }}</p>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->destination_country }}{{ $pkg->destination_city ? ', '.$pkg->destination_city : '' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->departure_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $pkg->priceFormatted() }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $pkg->seats_booked }} / {{ $pkg->available_seats ?? '∞' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$pkg->status] ?? '' }}">
                            {{ $labels[$pkg->status] ?? $pkg->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 flex gap-2">
                        <a href="{{ route('travel-agency.packages.show', $pkg) }}" class="text-blue-600 text-xs hover:underline">عرض</a>
                        @if(in_array($pkg->status, ['draft','pending_review']))
                        <a href="{{ route('travel-agency.packages.edit', $pkg) }}" class="text-amber-600 text-xs hover:underline">تعديل</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">
                        لا توجد باقات بعد.
                        <a href="{{ route('travel-agency.packages.create') }}" class="text-blue-600 hover:underline">أضف أول باقة</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $packages->links() }}
        </div>
    </div>
</div>
@endsection
