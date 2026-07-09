@extends('layouts.advertise')

@section('title', session('locale', 'ar') === 'ar' ? 'لنبدأ محادثة | اتصل بنا - نون' : "Let's start a conversation | Contact us - noon")
@section('description', session('locale', 'ar') === 'ar'
    ? 'استفد من حلولنا الإعلانية اليوم للوصول إلى أهدافك التسويقية أو البيعية عبر المواقع بغض النظر عن ميزانيتك.'
    : 'Leverage our ad solutions today to reach your marketing or sales objectives across locations and irrespective of your budget.')

@section('content')
    @include('portal.partials.advertise-request-form')
@endsection
