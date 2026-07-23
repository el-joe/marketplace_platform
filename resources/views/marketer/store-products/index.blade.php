@extends('layouts.marketer')

@section('title', __('marketer.nav.store_products'))
@section('page-title', __('marketer.nav.store_products'))

@section('content')

    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-bold text-gray-800">{{ __('marketer.nav.store_products') }}</h2>
            <p class="text-sm text-gray-500">{{ $products->total() }} products</p>
        </div>
        <a href="{{ route('marketer.store-products.create') }}"
            class="bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-4 py-2.5 transition-colors">
            + Add Product
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-3 mb-5">{{ session('success') }}</div>
    @endif

    @php
        $tabDefs = [
            null => 'All',
            'draft' => 'Draft',
            'pending_review' => 'Pending Review',
            'active' => 'Active',
            'paused' => 'Paused',
            'rejected' => 'Rejected',
        ];
    @endphp
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-100 pb-3">
        @foreach($tabDefs as $value => $label)
            @php $isCurrent = $statusFilter === $value; @endphp
            <a href="{{ route('marketer.store-products.index', $value ? ['status' => $value] : []) }}"
                class="text-xs font-semibold rounded-full px-3 py-1.5 transition-colors {{ $isCurrent ? 'bg-slate-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
                @if($value)<span class="ml-1 opacity-70">{{ $tabs[$value] ?? 0 }}</span>@endif
            </a>
        @endforeach
    </div>

    @if($products->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <div class="text-4xl mb-3">🛍️</div>
            <h3 class="font-bold text-gray-700 mb-1">No products yet</h3>
            <p class="text-sm text-gray-400 mb-5">Add your first own-branded product to start selling.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($products as $product)
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    @if($product->image_path)
                        <img src="{{ Storage::url($product->image_path) }}" class="w-full h-32 object-cover rounded-lg mb-3" alt="">
                    @endif
                    <h3 class="font-bold text-gray-800 text-sm">{{ $product->name_en }}</h3>
                    <p class="text-xs text-gray-500 mb-2">{{ $product->price }} {{ $product->currency }} — commission {{ number_format($product->platform_commission_rate / 100, 2) }}%</p>
                    <span class="text-xs font-semibold rounded-full px-2 py-0.5
                        {{ $product->status === 'active' ? 'bg-green-100 text-green-700' : ($product->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                        {{ $product->status }}
                    </span>
                    @if($product->status === 'rejected' && $product->rejection_reason)
                        <p class="text-xs text-red-500 mt-2">{{ $product->rejection_reason }}</p>
                    @endif
                    <div class="flex gap-2 mt-3">
                        @if(in_array($product->status, ['draft', 'rejected', 'paused']))
                            <a href="{{ route('marketer.store-products.edit', $product) }}" class="text-xs font-semibold text-slate-700 hover:underline">Edit</a>
                        @endif
                        @if(in_array($product->status, ['draft', 'rejected']))
                            <form method="POST" action="{{ route('marketer.store-products.submit', $product) }}">
                                @csrf
                                <button class="text-xs font-semibold text-emerald-600 hover:underline">Submit for review</button>
                            </form>
                        @endif
                        @if(in_array($product->status, ['draft', 'rejected', 'paused']))
                            <form method="POST" action="{{ route('marketer.store-products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-red-500 hover:underline">Delete</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-6">{{ $products->links() }}</div>
    @endif

@endsection
