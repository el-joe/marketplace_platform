@extends('layouts.admin')

@section('title', __('admin.coupons_section.edit_coupon') . ': ' . e($coupon->code))

@push('styles')
    @vite(['resources/js/components/flatpickr.js', 'resources/js/components/select2.js', 'resources/js/admin/coupons.js'])
@endpush

@section('content')
    <form id="coupon-form" method="POST" action="{{ route('admin.coupons.update', $coupon->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.coupons._form', ['mode' => 'edit', 'coupon' => $coupon])
    </form>
@endsection