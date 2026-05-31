@extends('layouts.partner')

@section('title', session('locale', 'ar') === 'ar' ? 'لوحة التحكم' : 'Dashboard')
@section('page-title', session('locale', 'ar') === 'ar' ? 'لوحة التحكم' : 'Dashboard')

@section('content')
@php
    $isAr = session('locale', 'ar') === 'ar';
    $vendorAdmin = auth()->guard('vendor')->user();
@endphp

{{-- Welcome banner --}}
<div class="bg-gradient-to-r from-yellow-400 to-yellow-300 rounded-2xl p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black text-gray-950">
                {{ $isAr ? 'مرحباً،' : 'Welcome back,' }}
                {{ $vendorAdmin?->name }}! 👋
            </h1>
            <p class="text-gray-700 text-sm mt-1">
                {{ $isAr ? 'إليك ملخص متجرك اليوم.' : 'Here\'s your store summary for today.' }}
            </p>
        </div>
        <div class="hidden sm:block text-5xl">🏪</div>
    </div>
</div>

{{-- Stats grid --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['💰', $isAr ? 'المبيعات اليوم' : "Today's Sales",     '0 د.إ', 'text-green-600', 'bg-green-50'],
        ['📦', $isAr ? 'الطلبات الجديدة' : 'New Orders',        '0',    'text-blue-600',  'bg-blue-50'],
        ['📋', $isAr ? 'المنتجات النشطة' : 'Active Products',   '0',    'text-purple-600','bg-purple-50'],
        ['⭐', $isAr ? 'التقييم' : 'Rating',                    '—',    'text-yellow-600','bg-yellow-50'],
    ] as $s)
    <div class="bg-white border border-gray-200 rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <span class="text-2xl">{{ $s[0] }}</span>
            <div class="w-8 h-8 {{ $s[4] }} rounded-full flex items-center justify-center">
                <span class="{{ $s[3] }} text-xs font-bold">↑</span>
            </div>
        </div>
        <div class="text-2xl font-black text-gray-900">{{ $s[2] }}</div>
        <div class="text-xs text-gray-500 mt-1">{{ $s[1] }}</div>
    </div>
    @endforeach
</div>

{{-- Quick actions --}}
<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white border border-gray-200 rounded-2xl p-5">
        <h3 class="font-bold text-gray-800 mb-3 text-sm">
            {{ $isAr ? 'الإجراءات السريعة' : 'Quick Actions' }}
        </h3>
        <div class="space-y-2">
            @foreach([
                [$isAr ? '➕ أضف منتجاً' : '➕ Add Product', '#'],
                [$isAr ? '📦 عرض الطلبات' : '📦 View Orders', '#'],
                [$isAr ? '💳 طلب سحب' : '💳 Request Payout', '#'],
            ] as $action)
            <a href="{{ $action[1] }}"
               class="block py-2 px-3 bg-gray-50 hover:bg-yellow-50 hover:text-yellow-700
                      text-gray-700 text-sm rounded-xl transition-colors">
                {{ $action[0] }}
            </a>
            @endforeach
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl p-5 md:col-span-2">
        <h3 class="font-bold text-gray-800 mb-3 text-sm">
            {{ $isAr ? 'حالة الحساب' : 'Account Status' }}
        </h3>
        <div class="space-y-3">
            @php
            $checks = [
                [$isAr ? 'الحساب محقق' : 'Account verified',          $vendor?->approved_at ? true : false],
                [$isAr ? 'المستندات مرفوعة' : 'Documents uploaded',    false],
                [$isAr ? 'الحساب البنكي مضاف' : 'Bank account added',  false],
                [$isAr ? 'أول منتج مضاف' : 'First product added',     false],
            ];
            @endphp
            @foreach($checks as $check)
            <div class="flex items-center gap-3">
                <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 {{ $check[1] ? 'bg-green-100' : 'bg-gray-100' }}">
                    @if($check[1])
                    <svg class="w-3 h-3 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20,6 9,17 4,12"/></svg>
                    @else
                    <svg class="w-3 h-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="8"/></svg>
                    @endif
                </div>
                <span class="text-sm {{ $check[1] ? 'text-gray-700' : 'text-gray-400' }}">{{ $check[0] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
