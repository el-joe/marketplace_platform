@extends('layouts.admin')

@push('head')
    @vite('resources/js/admin/cities.js')
@endpush

@section('title', 'Add City')

@section('content')
    <form method="POST" action="{{ route('admin.cities.store') }}" novalidate>
        @csrf
        @include('admin.cities._form', ['mode' => 'create'])
    </form>
@endsection