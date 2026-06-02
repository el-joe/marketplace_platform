@extends('layouts.portal')

@section('title', session('locale', 'ar') === 'ar' ? 'بيع على نون' : 'Sell on Noon')

@section('content')
    @include('portal.partials.hero')
    @include('portal.partials.why-sell')
    @include('portal.partials.how-it-works')
    @include('portal.partials.fulfillment')
    @include('portal.partials.smart-tools')
    @include('portal.partials.testimonials')
    @include('portal.partials.faq')
    @include('portal.partials.cta-footer')
@endsection
