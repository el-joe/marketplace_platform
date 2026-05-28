@extends('layouts.admin')

@section('title', 'Add Coupon')

@push('styles')
    @vite(['resources/js/components/flatpickr.js', 'resources/js/components/select2.js', 'resources/js/admin/coupons.js'])
@endpush

@section('content')
    <form id="coupon-form" method="POST" action="{{ route('admin.coupons.store') }}" novalidate>
        @csrf
        @include('admin.coupons._form', ['mode' => 'create'])
    </form>
@endsection