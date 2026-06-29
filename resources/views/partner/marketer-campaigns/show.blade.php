@extends('layouts.partner')

@section('title', $campaign->name)
@section('page-title', $campaign->name)

@section('content')

<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('partner.marketer-campaigns.index') }}" class="hover:text-white">← حملات المسوّقين</a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['نقرات', number_format($campaign->total_clicks), 'text-blue-400'],
            ['تحويلات', number_format($campaign->total_conversions), 'text-purple-400'],
            ['إيراد', number_format($campaign->total_revenue_cents / 100, 2) . ' ' . ($campaign->vendor?->country?->currency_code ?? ''), 'text-green-400'],
            ['معدل التحويل', $campaign->total_clicks > 0 ? round($campaign->total_conversions / $campaign->total_clicks * 100, 2) . '%' : '0%', 'text-yellow-400'],
        ] as [$label, $value, $color])
            <div class="bg-white/5 border border-white/10 rounded-xl p-4">
                <p class="text-xs text-gray-400 uppercase tracking-wide">{{ $label }}</p>
                <p class="text-xl font-bold {{ $color }} mt-1">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    {{-- Details --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-5">
        <h3 class="font-bold text-gray-800 mb-4">تفاصيل الحملة</h3>
        <div class="space-y-2 text-sm">
            @foreach([
                'المسوّق'       => $campaign->marketer?->name ?? '—',
                'النوع'         => ucfirst(str_replace('_', ' ', $campaign->campaign_type)),
                'نسبة العمولة'  => $campaign->commission_rate . '%',
                'تاريخ البدء'   => $campaign->starts_at?->format('d M Y') ?? '—',
                'تاريخ الانتهاء' => $campaign->ends_at?->format('d M Y') ?? 'مفتوح',
                'الحالة'        => ucfirst($campaign->status),
            ] as $label => $value)
                <div class="flex justify-between">
                    <span class="text-gray-400">{{ $label }}</span>
                    <span class="font-medium text-gray-700">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Products in this campaign belonging to this vendor --}}
    @if($campaign->products->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">منتجاتك في الحملة</h3>
            <div class="space-y-2">
                @foreach($campaign->products as $cp)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                        <span class="text-sm font-medium text-gray-700">
                            {{ $cp->vendorListing?->product?->name_en ?? '—' }}
                        </span>
                        <span class="text-sm text-gray-500">
                            {{ number_format($cp->vendorListing?->sale_price / 100, 2) }} {{ $cp->vendorListing?->currency ?? $campaign->vendor?->country?->currency_code ?? '' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

@endsection
