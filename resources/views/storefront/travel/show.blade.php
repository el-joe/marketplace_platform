@extends('layouts.storefront')

@section('title', $package->title_ar ?: $package->title_en)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-8">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500">
        <a href="{{ route('travel.index', request()->route('country')) }}" class="hover:text-blue-600">باقات السفر</a>
        <span class="mx-2">›</span>
        <span class="text-gray-800">{{ $package->title_ar ?: $package->title_en }}</span>
    </nav>

    {{-- Media Gallery --}}
    @if($package->media->count())
    <div class="grid grid-cols-3 gap-3 h-80 rounded-2xl overflow-hidden">
        @php $mediaItems = $package->media->take(5); @endphp
        <div class="col-span-2 row-span-2">
            @if($mediaItems->first()?->media_type === 'video')
            <video src="{{ $mediaItems->first()->url() }}" controls class="w-full h-full object-cover"></video>
            @else
            <img src="{{ $mediaItems->first()?->url() }}" class="w-full h-full object-cover">
            @endif
        </div>
        @foreach($mediaItems->skip(1)->take(4) as $m)
        @if($m->media_type === 'image')
        <img src="{{ $m->url() }}" class="w-full h-full object-cover">
        @else
        <video src="{{ $m->url() }}" class="w-full h-full object-cover"></video>
        @endif
        @endforeach
    </div>
    @else
    <div class="w-full h-64 bg-gradient-to-br from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center text-blue-300 text-8xl">✈</div>
    @endif

    <div class="grid grid-cols-3 gap-8">
        {{-- Main info --}}
        <div class="col-span-2 space-y-6">
            <div>
                <h1 class="text-3xl font-black text-gray-900 mb-1">{{ $package->title_ar ?: $package->title_en }}</h1>
                <p class="text-gray-400 text-sm">{{ $package->title_en }}</p>
                <p class="text-blue-600 font-medium mt-2">
                    📍 {{ $package->destination_country }}{{ $package->destination_city ? '، '.$package->destination_city : '' }}
                </p>
            </div>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <p class="text-2xl font-black text-blue-600">{{ $package->duration_days }}</p>
                    <p class="text-xs text-gray-500 mt-1">أيام</p>
                </div>
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    <p class="text-2xl font-black text-blue-600">{{ $package->duration_nights }}</p>
                    <p class="text-xs text-gray-500 mt-1">ليالي</p>
                </div>
                <div class="bg-blue-50 rounded-xl p-4 text-center">
                    @if($package->available_seats !== null)
                    <p class="text-2xl font-black text-blue-600">{{ $package->seatsRemaining() }}</p>
                    <p class="text-xs text-gray-500 mt-1">مقعد متاح</p>
                    @else
                    <p class="text-2xl font-black text-blue-600">∞</p>
                    <p class="text-xs text-gray-500 mt-1">مقاعد متاحة</p>
                    @endif
                </div>
            </div>

            {{-- Inclusions --}}
            @if($package->inclusions)
            @php
            $inclusionLabels = ['flights'=>'تذاكر طيران','hotel'=>'إقامة فندقية','meals'=>'وجبات','tours'=>'جولات سياحية','visa'=>'تأشيرة','insurance'=>'تأمين سفر','transfers'=>'مواصلات'];
            $inclusionIcons  = ['flights'=>'✈','hotel'=>'🏨','meals'=>'🍽','tours'=>'🗺','visa'=>'📋','insurance'=>'🛡','transfers'=>'🚌'];
            @endphp
            <div>
                <h3 class="font-bold text-gray-900 mb-3">ما يشمله السعر</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($package->inclusions as $item)
                    <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full px-3 py-1 text-sm font-medium">
                        {{ $inclusionIcons[$item] ?? '✓' }} {{ $inclusionLabels[$item] ?? $item }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Description --}}
            @if($package->description_ar || $package->description_en)
            <div class="prose prose-sm max-w-none">
                <h3 class="font-bold text-gray-900 mb-2">عن هذه الباقة</h3>
                <p class="text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $package->description_ar ?: $package->description_en }}</p>
            </div>
            @endif

            {{-- Agency --}}
            <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-4">
                @if($package->agency->logoUrl())
                <img src="{{ $package->agency->logoUrl() }}" class="w-12 h-12 rounded-xl object-cover">
                @else
                <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                    {{ mb_substr($package->agency->name, 0, 1) }}
                </div>
                @endif
                <div>
                    <p class="font-semibold text-gray-900">{{ $package->agency->name }}</p>
                    <p class="text-xs text-gray-500">وكالة سفر معتمدة</p>
                </div>
            </div>
        </div>

        {{-- Booking Card --}}
        <div class="col-span-1">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-lg p-6 sticky top-24 space-y-4">
                <div>
                    <p class="text-3xl font-black text-gray-900">{{ $package->priceFormatted() }}</p>
                    <p class="text-xs text-gray-400">السعر للفرد الواحد</p>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>المغادرة</span>
                        <span class="font-medium text-gray-900">{{ $package->departure_date->format('d M Y') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>العودة</span>
                        <span class="font-medium text-gray-900">{{ $package->return_date->format('d M Y') }}</span>
                    </div>
                </div>

                @if(($package->seatsRemaining() ?? 1) > 0)
                @auth
                <a href="{{ route('travel.book', $package) }}"
                   class="block w-full bg-blue-600 hover:bg-blue-500 text-white text-center font-bold py-3.5 rounded-xl transition-colors">
                    احجز الآن
                </a>
                @else
                <a href="{{ route('login') }}"
                   class="block w-full bg-blue-600 hover:bg-blue-500 text-white text-center font-bold py-3.5 rounded-xl transition-colors">
                    سجّل الدخول للحجز
                </a>
                @endauth
                @else
                <button disabled class="block w-full bg-gray-200 text-gray-500 text-center font-bold py-3.5 rounded-xl cursor-not-allowed">
                    المقاعد مكتملة
                </button>
                @endif

                <p class="text-xs text-gray-400 text-center">بعد الحجز ستحتاج لرفع صورة جواز السفر وتوقيع العقد</p>
            </div>
        </div>
    </div>
</div>
@endsection
