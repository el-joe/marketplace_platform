@extends('layouts.admin')

@section('title', __('admin.admin_product_listings.edit_listing_title'))

@push('styles')
    @vite(['resources/js/components/select2.js'])
@endpush

@section('content')
<div class="p-6">
    <form method="POST" action="{{ route('admin.admin-product-listings.update', $listing) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.admin-product-listings._form', ['listing' => $listing])
    </form>
</div>
@endsection
