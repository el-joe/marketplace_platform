@extends('layouts.advertise')

@section('title', session('locale', 'ar') === 'ar' ? 'حلول الإعلانات ذات الشعبية | المعلنين - نون' : 'Popular Ad Solutions | Advertisers - noon')
@section('description', session('locale', 'ar') === 'ar'
    ? 'لا تبيع على نون، ولكنك تتطلع إلى تعزيز ظهورك لجماهير فئة كبيرة عبر المناطق الجغرافية؟ استفد من حلولنا الإعلانية للوصول إلى عملائك المستهدفين.'
    : 'Not selling on noon, but looking to boost your visibility to large audiences across geographies? Leverage our ad solutions to reach your targeted customers.')

@section('content')
    @include('portal.partials.advertisers-hero')
    @include('portal.partials.advertisers-solutions')
@endsection
