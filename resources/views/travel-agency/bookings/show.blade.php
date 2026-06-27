@extends('layouts.travel-agency')

@section('title', 'حجز ' . $booking->booking_number)

@section('content')
@php
$statusColors = ['pending_documents'=>'bg-amber-100 text-amber-700','confirmed'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-red-100 text-red-700','completed'=>'bg-blue-100 text-blue-700'];
$statusLabels = ['pending_documents'=>'بانتظار الوثائق','confirmed'=>'مؤكد','cancelled'=>'ملغى','completed'=>'مكتمل'];
@endphp

<div class="space-y-6 max-w-3xl">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('travel-agency.bookings.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-black text-gray-900">{{ $booking->booking_number }}</h1>
            <span class="px-2.5 py-0.5 rounded-full text-sm font-medium {{ $statusColors[$booking->status] ?? '' }}">
                {{ $statusLabels[$booking->status] ?? $booking->status }}
            </span>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-5">
        {{-- Package details --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-3">
            <h2 class="font-bold text-gray-800 border-b border-gray-100 pb-2">تفاصيل الباقة</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">الباقة</dt>
                    <dd class="font-medium text-gray-900">
                        <a href="{{ route('travel-agency.packages.show', $booking->package) }}" class="text-blue-600 hover:underline">
                            {{ $booking->package->title_ar ?: $booking->package->title_en }}
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">الوجهة</dt>
                    <dd class="text-gray-700">{{ $booking->package->destination_country }}{{ $booking->package->destination_city ? '، '.$booking->package->destination_city : '' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">موعد السفر</dt>
                    <dd class="text-gray-700">{{ $booking->package->departure_date->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">موعد العودة</dt>
                    <dd class="text-gray-700">{{ $booking->package->return_date->format('d M Y') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Customer details --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-3">
            <h2 class="font-bold text-gray-800 border-b border-gray-100 pb-2">بيانات العميل</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">الاسم</dt>
                    <dd class="font-medium text-gray-900">{{ $booking->customer->name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">البريد الإلكتروني</dt>
                    <dd class="text-gray-700">{{ $booking->customer->email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">عدد المسافرين</dt>
                    <dd class="font-medium text-gray-900">{{ $booking->travelers_count }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">إجمالي الدفع</dt>
                    <dd class="font-bold text-gray-900">{{ $booking->totalFormatted() }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Documents --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-3">
        <h2 class="font-bold text-gray-800 border-b border-gray-100 pb-2">الوثائق والعقد</h2>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-500 mb-1">جواز السفر</p>
                @if($booking->passport_file_path)
                    <a href="{{ asset('storage/'.$booking->passport_file_path) }}" target="_blank"
                       class="inline-flex items-center gap-1 text-blue-600 hover:underline font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        تنزيل الجواز
                    </a>
                @else
                    <span class="text-gray-400">لم يُرفع بعد</span>
                @endif
            </div>
            <div>
                <p class="text-gray-500 mb-1">العقد</p>
                @if($booking->contract_signed_at)
                    <p class="text-emerald-600 font-medium">موقّع — {{ $booking->contract_signed_at->format('d M Y') }}</p>
                @else
                    <span class="text-gray-400">لم يُوقّع بعد</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Status actions --}}
    @if(in_array($booking->status, ['pending_documents', 'confirmed']))
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-bold text-gray-800 mb-4">إجراءات الحجز</h2>
        <div class="flex gap-3 flex-wrap">
            @if($booking->status === 'pending_documents')
            <form method="POST" action="{{ route('travel-agency.bookings.status', $booking) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="confirmed">
                <button type="submit"
                        class="px-5 py-2.5 bg-emerald-500 text-white rounded-xl font-bold hover:bg-emerald-400 transition-colors"
                        onclick="return confirm('تأكيد الحجز؟')">
                    ✓ تأكيد الحجز
                </button>
            </form>
            @endif
            <form method="POST" action="{{ route('travel-agency.bookings.status', $booking) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="cancelled">
                <button type="submit"
                        class="px-5 py-2.5 bg-red-500 text-white rounded-xl font-bold hover:bg-red-400 transition-colors"
                        onclick="return confirm('إلغاء الحجز؟ لا يمكن التراجع عن هذا الإجراء.')">
                    ✗ إلغاء الحجز
                </button>
            </form>
        </div>
        @error('status')
        <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
    @endif

    <div class="text-xs text-gray-400">
        تاريخ الحجز: {{ $booking->created_at->format('d M Y H:i') }}
    </div>
</div>
@endsection
