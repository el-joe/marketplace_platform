@extends('layouts.admin')

@section('title', 'New Admin Product Listing')

@section('content')
<div class="p-6">
    <form method="POST" action="{{ route('admin.admin-product-listings.store') }}" novalidate>
        @csrf
        @include('admin.admin-product-listings._form')
    </form>
</div>
@endsection
