@extends('layouts.admin')

@push('head')
    @vite('resources/js/admin/countries.js')
@endpush

@section('title', 'Add Country')

@section('content')
    <form id="country-form" method="POST" action="{{ route('admin.countries.store') }}" novalidate>
        @csrf
        @include('admin.countries._form', ['mode' => 'create'])
    </form>
@endsection