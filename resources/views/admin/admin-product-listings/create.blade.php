@extends('layouts.admin')

@section('title', __('admin.admin_product_listings.new_listing_title'))

@section('content')
<div class="p-6">
    <form method="POST" action="{{ route('admin.admin-product-listings.store') }}" novalidate>
        @csrf
        @include('admin.admin-product-listings._form')
    </form>
</div>
@endsection
