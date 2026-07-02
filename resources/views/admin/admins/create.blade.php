@extends('layouts.admin')

@section('title', __('admin.admins_section.add_administrator'))

@push('styles')
    @vite(['resources/js/components/select2.js', 'resources/js/admin/admins.js'])
@endpush

@section('content')
    <form id="admin-form" method="POST" action="{{ route('admin.admins.store') }}" novalidate>
        @csrf
        @include('admin.admins._form', ['mode' => 'create'])
    </form>
@endsection