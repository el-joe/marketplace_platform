@extends('layouts.advertise')

@section('title', session('locale', 'ar') === 'ar' ? 'حلول الإعلانات ذات الشعبية | العلامات التجارية - نون' : 'Popular Ad Solutions | Brands - noon')
@section('description', session('locale', 'ar') === 'ar'
    ? 'قم بتعزيز الوعي بعلامتك التجارية، والوصول إلى عملاء أكثر، والتواصل مع العملاء من خلال الاستفادة من المنتجات الإستراتيجية لإعلانات نون'
    : 'Boost your brand awareness, reach large audiences, and connect with customers by leveraging noon ads strategic products.')

@section('content')
    @include('portal.partials.brands-hero')
    @include('portal.partials.brands-solutions')
    @include('portal.partials.brands-testimonials')
@endsection
