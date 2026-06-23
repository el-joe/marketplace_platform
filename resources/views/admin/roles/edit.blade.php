@extends('layouts.admin')

@section('title', 'Edit Role: ' . e($role->name))

@push('styles')
    @vite(['resources/js/admin/admins.js'])
@endpush

@section('content')
    <form id="role-form" method="POST" action="{{ route('admin.roles.update', $role->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.roles._form', ['mode' => 'edit'])
    </form>
@endsection