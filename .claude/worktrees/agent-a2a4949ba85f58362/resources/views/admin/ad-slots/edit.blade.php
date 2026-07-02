@extends('layouts.admin')

@section('title', __('admin.ad_campaigns.edit_ad_slot'))

@section('content')

    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <a href="{{ route('admin.ad-slots.index') }}" class="hover:text-primary-600">{{ __('admin.ad_campaigns.ad_slots') }}</a>
            <span>/</span>
            <span class="text-gray-800 font-medium">{{ __('common.edit') }}</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.ad_campaigns.edit_ad_slot') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5 font-mono">{{ $adSlot->slot_code }}</p>
    </div>

    <form method="POST" action="{{ route('admin.ad-slots.update', $adSlot->id) }}">
        @csrf
        @method('PUT')
        @include('admin.ad-slots._form', ['adSlot' => $adSlot])
    </form>

@endsection
