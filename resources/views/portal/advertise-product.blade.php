@extends('layouts.advertise')

@section('title', session('locale', 'ar') === 'ar' ? 'إعلانات المنتجات | نون' : 'Product Ads | noon')
@section('description', session('locale', 'ar') === 'ar'
    ? 'قم بتعزيز رؤية منتجاتك على مسار التحويل السفلي من خلال الإعلانات المستهدفة التي تصل إلى قاعدة عملاء أكبر وتمكّن النمو.'
    : 'Amplify your products visibility on the lower funnel with targeted ads that reach a larger customer base and enable growth.')

@section('content')
    @include('portal.partials.product-hero')
    @include('portal.partials.product-quick-guide')
    @include('portal.partials.product-faq')
@endsection
