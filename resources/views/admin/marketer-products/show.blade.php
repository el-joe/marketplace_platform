@extends('layouts.admin')

@section('title', $product->name_en)

@section('content')

<x-card>
    <div class="flex justify-between items-start mb-4">
        <div>
            <h2 class="text-xl font-bold text-gray-800">{{ $product->name_en }}</h2>
            <p class="text-sm text-gray-500">{{ $product->name_ar }}</p>
        </div>
        <span class="badge badge-{{ ['active' => 'success', 'pending_review' => 'warning', 'rejected' => 'danger'][$product->status] ?? 'gray' }}">
            {{ $product->status }}
        </span>
    </div>

    @if($product->image_path)
        <img src="{{ Storage::url($product->image_path) }}" class="w-40 h-40 object-cover rounded-lg mb-4" alt="">
    @endif

    <dl class="grid grid-cols-2 gap-4 text-sm mb-4">
        <div><dt class="text-gray-400">Marketer</dt><dd class="font-medium">{{ $product->marketer->name }}</dd></div>
        <div><dt class="text-gray-400">Price</dt><dd class="font-medium">{{ $product->price }} {{ $product->currency }}</dd></div>
        <div><dt class="text-gray-400">Platform Commission</dt><dd class="font-medium">{{ number_format($product->platform_commission_rate / 100, 2) }}%</dd></div>
        <div><dt class="text-gray-400">Stock</dt><dd class="font-medium">{{ $product->stock_quantity }}</dd></div>
        <div><dt class="text-gray-400">SKU</dt><dd class="font-medium">{{ $product->sku ?? '—' }}</dd></div>
        <div><dt class="text-gray-400">Digital</dt><dd class="font-medium">{{ $product->is_digital ? 'Yes' : 'No' }}</dd></div>
    </dl>

    @if($product->description_en)
        <p class="text-sm text-gray-700 mb-4">{{ $product->description_en }}</p>
    @endif

    @if($product->rejection_reason)
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 mb-4">
            Rejection reason: {{ $product->rejection_reason }}
        </div>
    @endif

    @if($product->status === 'pending_review')
        <div class="flex gap-3">
            <form method="POST" action="{{ route('admin.marketer-products.approve', $product) }}">
                @csrf
                <button class="btn btn-success">Approve</button>
            </form>
            <button class="btn btn-danger" onclick="document.getElementById('reject-form').classList.remove('hidden')">Reject</button>
        </div>
        <form id="reject-form" method="POST" action="{{ route('admin.marketer-products.reject', $product) }}" class="hidden mt-4">
            @csrf
            <textarea name="reason" rows="3" class="form-input w-full" placeholder="Rejection reason" required></textarea>
            <button class="btn btn-danger mt-2">Confirm Reject</button>
        </form>
    @endif
</x-card>

@endsection
