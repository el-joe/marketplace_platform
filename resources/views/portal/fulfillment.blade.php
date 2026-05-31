@extends('layouts.portal')

@section('title', session('locale', 'ar') === 'ar' ? 'الشحن والتوصيل' : 'Fulfillment')

@section('content')
    @include('portal.partials.fulfillment')
    @include('portal.partials.cta-footer')
@endsection