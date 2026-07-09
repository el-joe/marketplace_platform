@extends('layouts.advertise')

@section('title', session('locale', 'ar') === 'ar' ? 'إعلانات العرض | نون' : 'Display Ads | noon')
@section('description', session('locale', 'ar') === 'ar'
    ? 'قم بتسليط الضوء على الحملات المحددة التي ترغب في الترويج لها، بما في ذلك عمليات الإطلاق الجديدة أو منتجات التصفية أو العروض الموسمية لاستهداف الجماهير التي من المحتمل أن تكون مهتمة.'
    : 'Highlight specific campaigns you want to push, including new launches, clearance items, or seasonal offerings to target audiences likely to be interested.')

@section('content')
    @include('portal.partials.display-hero')
    @include('portal.partials.display-quick-guide')
    @include('portal.partials.display-faq')
@endsection
