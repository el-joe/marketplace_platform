@extends('layouts.travel-agency')

@section('title', 'الحجوزات')

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-gray-900">الحجوزات</h1>
    </div>

    {{-- Status filter tabs --}}
    @php
    $statuses = [
        ''                   => 'الكل',
        'pending_documents'  => 'بانتظار الوثائق',
        'confirmed'          => 'مؤكدة',
        'cancelled'          => 'ملغاة',
        'completed'          => 'مكتملة',
    ];
    $current = request('status', '');
    @endphp
    <div class="flex gap-2 flex-wrap">
        @foreach($statuses as $val => $label)
        <a href="{{ route('travel-agency.bookings.index', $val ? ['status' => $val] : []) }}"
           class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors
                  {{ $current === $val ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">رقم الحجز</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">العميل</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الباقة</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">المسافرون</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الإجمالي</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">الحالة</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">التاريخ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($bookings as $bk)
                @php
                $colors = ['pending_documents'=>'bg-amber-100 text-amber-700','confirmed'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-red-100 text-red-700','completed'=>'bg-blue-100 text-blue-700'];
                $labels = ['pending_documents'=>'بانتظار الوثائق','confirmed'=>'مؤكد','cancelled'=>'ملغى','completed'=>'مكتمل'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $bk->booking_number }}</td>
                    <td class="px-4 py-3 text-gray-800">{{ $bk->customer->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $bk->package->title_ar ?: $bk->package->title_en }}</td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ $bk->travelers_count }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900">{{ $bk->totalFormatted() }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$bk->status] ?? '' }}">
                            {{ $labels[$bk->status] ?? $bk->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $bk->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('travel-agency.bookings.show', $bk) }}" class="text-blue-600 text-xs hover:underline">تفاصيل</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-gray-400 text-sm">لا توجد حجوزات بعد.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $bookings->links() }}
        </div>
    </div>
</div>
@endsection
