@extends('layouts.partner')
@section('title', __('partner.warehouses.page_title'))
@section('page-title', __('partner.warehouses.page_title_full'))

@push('scripts')
    @vite('resources/js/partner/warehouses.js')
@endpush

@section('content')

    {{-- My seller-owned warehouses --}}
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('partner.warehouses.my_warehouses') }}</h2>
        <a href="{{ route('partner.warehouses.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('partner.warehouses.register_warehouse') }}
        </a>
    </div>

    @if($myWarehouses->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-10 text-center mb-8">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 mx-auto mb-3 text-gray-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016 2.993 2.993 0 0 0 2.25-1.016 3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" /></svg>
            <p class="text-gray-600 font-medium mb-1">{{ __('partner.warehouses.no_warehouse_yet') }}</p>
            <p class="text-sm text-gray-400 mb-4">{{ __('partner.warehouses.no_warehouse_hint') }}</p>
            <a href="{{ route('partner.warehouses.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-xl hover:bg-primary-700 transition-colors">
                {{ __('partner.warehouses.register_warehouse') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
            @foreach($myWarehouses as $warehouse)
                @php
                    $totalUnits = $warehouse->warehouseInventories->sum('quantity_on_hand');
                    $totalSkus  = $warehouse->warehouseInventories->count();
                    $pct        = ($warehouse->total_capacity_m3 && $warehouse->total_capacity_m3 > 0)
                        ? round(($warehouse->used_capacity_m3 / $warehouse->total_capacity_m3) * 100, 1)
                        : null;
                @endphp
                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $warehouse->code }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">{{ __('partner.warehouses.seller_owned') }}</span>
                                    @unless($warehouse->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">{{ __('partner.warehouses.inactive') }}</span>
                                    @endunless
                                </div>
                                <h3 class="font-bold text-gray-900 text-lg">{{ $warehouse->name }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5">{{ session('locale', 'ar') === 'ar' ? ($warehouse->country->name_ar ?? $warehouse->country->name_en) : $warehouse->country->name_en }}</p>
                            </div>
                            <a href="{{ route('partner.warehouses.show', $warehouse->id) }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                {{ __('partner.warehouses.manage') }}
                            </a>
                        </div>
                    </div>

                    <div class="p-5 grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($totalUnits) }}</p>
                            <p class="text-xs text-gray-500">{{ __('partner.warehouses.total_units') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $totalSkus }}</p>
                            <p class="text-xs text-gray-500">{{ __('partner.warehouses.skus_stored') }}</p>
                        </div>
                    </div>

                    @if($pct !== null)
                        <div class="px-5 pb-5">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-500">{{ __('partner.warehouses.capacity_used') }}</span>
                                <span class="font-medium">{{ $pct }}%</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full">
                                <div class="h-full rounded-full {{ $pct > 90 ? 'bg-red-500' : 'bg-green-500' }}" style="width: {{ min(100, $pct) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mb-6">
            <a href="{{ route('partner.warehouses.transfers.index') }}" class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 bg-white text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                {{ __('partner.warehouses.view_all_transfers') }}
            </a>
        </div>
    @endif

    {{-- Platform FBN warehouses (read-only) --}}
    <h2 class="text-lg font-semibold text-gray-900 mb-1">{{ __('partner.warehouses.platform_warehouses_title') }}</h2>
    <p class="text-sm text-gray-500 mb-4">{{ __('partner.warehouses.platform_warehouses_subtitle') }}</p>

    @if($platformWarehouses->isEmpty())
        <div class="bg-gray-50 rounded-2xl border border-dashed border-gray-200 p-8 text-center text-gray-400 text-sm">
            {{ __('partner.warehouses.no_platform_stock') }}
            <a href="{{ route('partner.fulfillment.index') }}" class="text-primary-600 hover:underline">{{ __('partner.warehouses.request_inbound_shipment') }}</a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($platformWarehouses as $warehouse)
                <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">{{ __('partner.warehouses.platform_fbn') }}</span>
                        <span class="text-xs text-amber-600 font-medium">{{ __('partner.warehouses.read_only') }}</span>
                    </div>
                    <h3 class="font-semibold text-gray-800">{{ $warehouse->name }}</h3>
                    <p class="text-xs text-gray-500 mb-3">{{ session('locale', 'ar') === 'ar' ? ($warehouse->country->name_ar ?? $warehouse->country->name_en) : $warehouse->country->name_en }}</p>
                    <a href="{{ route('partner.warehouses.show', $warehouse->id) }}" class="text-sm text-primary-600 hover:underline">
                        {{ __('partner.warehouses.view_inventory_here') }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif

@endsection
