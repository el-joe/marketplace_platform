@extends('layouts.partner')

@section('title', 'Packaging Supplies')

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Packaging Supplies</h1>
            <p class="text-sm text-gray-500 mt-0.5">Browse available packaging materials. Request what you need.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('partner.packaging-supplies.my-requests') }}" class="btn btn-secondary">My Requests</a>
            <a href="{{ route('partner.packaging-supplies.request') }}" class="btn btn-primary">+ New Request</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    @if($supplies->isEmpty())
        <div class="card p-10 text-center text-gray-400">No packaging supplies available at the moment.</div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($supplies as $supply)
                <div class="card p-5 flex gap-4">
                    @if($supply->image_path)
                        <img src="{{ asset($supply->image_path) }}" alt="{{ $supply->name_en }}"
                             class="w-16 h-16 object-cover rounded-lg shrink-0">
                    @else
                        <div class="w-16 h-16 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 shrink-0 text-2xl">
                            @if($supply->type === 'box') 📦
                            @elseif($supply->type === 'bag') 🛍
                            @elseif($supply->type === 'label') 🏷
                            @elseif($supply->type === 'tape') 🎗
                            @else 📋
                            @endif
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $supply->name_en }}</p>
                                <p class="text-xs text-gray-400 font-arabic">{{ $supply->name_ar }}</p>
                            </div>
                            <span class="badge {{ $supply->typeBadgeClass() }} shrink-0">{{ ucfirst($supply->type) }}</span>
                        </div>
                        @if($supply->size)
                            <p class="text-xs text-gray-500 mt-1">{{ $supply->size }}</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-sm font-bold {{ $supply->isFree() ? 'text-green-600' : 'text-gray-900' }}">
                                {{ $supply->unit_cost_formatted }}
                            </span>
                            @if($supply->stock_available !== null)
                                <span class="text-xs text-gray-400">{{ number_format($supply->stock_available) }} in stock</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('partner.packaging-supplies.request') }}" class="btn btn-primary">
                Request Packaging Supplies
            </a>
        </div>
    @endif

@endsection
