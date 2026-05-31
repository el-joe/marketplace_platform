@extends('layouts.portal')

@section('title', session('locale', 'ar') === 'ar' ? 'الأدوات الذكية' : 'Smart Tools')

@section('content')
    @include('portal.partials.smart-tools')
    @include('portal.partials.cta-footer')
@endsection