@extends('layouts.marketer')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')

    <form method="POST" action="{{ route('marketer.store-products.update', $product) }}" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-100 p-6 max-w-2xl">
        @csrf
        @method('PUT')
        @include('marketer.store-products._form', ['product' => $product])
        <button class="bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-5 py-2.5 mt-4">
            Save Changes
        </button>
    </form>

@endsection
