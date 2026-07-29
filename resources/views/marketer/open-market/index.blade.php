@extends('layouts.marketer')

@section('title', 'Open Market')
@section('page-title', 'Open Market')

@section('content')

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">Open Market</h2>
            <p class="text-sm text-gray-500">{{ $products->total() }} products from other influencers you can promote</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-3 mb-5">{{ session('success') }}</div>
    @endif

    <form method="GET" class="flex flex-wrap gap-3 items-end mb-6 bg-white rounded-2xl border border-gray-100 p-4">
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Category</label>
            <select name="category_id" class="text-sm rounded-lg border-gray-200">
                <option value="">All</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($filters['category_id'] == $category->id)>
                        {{ app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Country</label>
            <select name="country_id" class="text-sm rounded-lg border-gray-200">
                <option value="">All</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" @selected($filters['country_id'] == $country->id)>{{ $country->name_en }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Min price</label>
            <input type="number" name="min_price" value="{{ $filters['min_price'] }}" min="0" class="text-sm rounded-lg border-gray-200 w-28">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1">Max price</label>
            <input type="number" name="max_price" value="{{ $filters['max_price'] }}" min="0" class="text-sm rounded-lg border-gray-200 w-28">
        </div>
        <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg px-4 py-2">Filter</button>
        <a href="{{ route('marketer.open-market.index') }}" class="text-sm font-semibold text-gray-500 hover:underline px-2 py-2">Reset</a>
    </form>

    @if($products->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <div class="text-4xl mb-3">🛒</div>
            <h3 class="font-bold text-gray-700 mb-1">No products found</h3>
            <p class="text-sm text-gray-400">Try adjusting your filters.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($products as $product)
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    @if($product->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" class="w-full h-32 object-cover rounded-lg mb-3" alt="">
                    @endif
                    <h3 class="font-bold text-gray-800 text-sm">{{ $product->name }}</h3>
                    <p class="text-xs text-gray-500">by {{ $product->marketer?->public_name }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $product->price }} {{ $product->currency }}
                        — commission {{ number_format($product->platform_commission_rate / 100, 2) }}%
                    </p>
                    @if($product->category)
                        <span class="inline-block text-xs font-semibold rounded-full px-2 py-0.5 bg-gray-100 text-gray-600 mt-2">
                            {{ app()->getLocale() === 'ar' ? $product->category->name_ar : $product->category->name_en }}
                        </span>
                    @endif

                    <div class="mt-3">
                        @if($product->already_promoted_source)
                            <button type="button" disabled
                                title="You've already promoted a product from this influencer this month."
                                class="w-full bg-gray-100 text-gray-400 text-xs font-bold rounded-xl px-4 py-2.5 cursor-not-allowed">
                                Already Promoted This Marketer
                            </button>
                        @else
                            <form method="POST" action="{{ route('marketer.open-market.promote', $product) }}">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-yellow-400 hover:bg-yellow-300 text-slate-900 text-xs font-bold rounded-xl px-4 py-2.5 transition-colors">
                                    Promote This Product
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
    @endif

@endsection
