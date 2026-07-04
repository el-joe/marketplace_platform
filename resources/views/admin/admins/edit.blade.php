@extends('layouts.admin')

@section('title', __('admin.admins_section.edit_administrator_title', ['name' => e($model->name)]))

@push('styles')
    @vite(['resources/js/components/select2.js', 'resources/js/admin/admins.js'])
@endpush

@section('content')
    <form id="admin-form" method="POST" action="{{ route('admin.admins.update', $model->id) }}" novalidate>
        @csrf
        @method('PUT')
        @include('admin.admins._form', ['mode' => 'edit'])
    </form>
@endsection