@extends('layouts.portal')

@section('title', session('locale', 'ar') === 'ar' ? 'كيف تبدأ' : 'How It Works')

@section('content')
    @include('portal.partials.how-it-works')
    @include('portal.partials.cta-footer')
@endsection