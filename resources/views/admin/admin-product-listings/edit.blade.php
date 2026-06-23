@extends('layouts.admin')

@section('title', 'Edit Listing')

@section('content')
<div class="p-6">
    <form method="POST" action="{{ route('admin.admin-product-listings.update', $listing) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.admin-product-listings._form', ['listing' => $listing])
    </form>
</div>
@endsection
