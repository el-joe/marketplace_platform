@extends('layouts.partner')

@section('title', 'حملات المسوّقين')
@section('page-title', 'حملات المسوّقين')

@section('content')

<div class="space-y-4">

    <div class="bg-white/5 border border-white/10 rounded-xl p-4">
        <p class="text-sm text-gray-400">
            عرض الحملات التسويقية التي يروّج فيها المسوّقون لمنتجاتك. يمكنك متابعة النقرات، التحويلات، وطلبات العينات.
        </p>
    </div>

    @if($campaigns->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-200 p-12 text-center">
            <p class="text-4xl mb-3">📣</p>
            <p class="font-semibold text-gray-700">لا توجد حملات بعد</p>
            <p class="text-sm text-gray-400 mt-1">سيظهر هنا المسوّقون الذين يروّجون لمنتجاتك.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($campaigns as $campaign)
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="font-semibold text-gray-800 truncate">{{ $campaign->name }}</h3>
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                    {{ $campaign->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500">المسوّق: <span class="font-medium text-gray-700">{{ $campaign->marketer?->name ?? '—' }}</span></p>
                            <p class="text-xs text-gray-400 mt-1">
                                العمولة: <strong>{{ $campaign->commission_rate }}%</strong>
                                &nbsp;·&nbsp;
                                {{ $campaign->starts_at?->format('d M Y') }} – {{ $campaign->ends_at?->format('d M Y') ?? '∞' }}
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-lg font-bold text-gray-800">{{ number_format($campaign->total_clicks) }}</p>
                            <p class="text-xs text-gray-400">نقرات</p>
                            <p class="text-sm font-bold text-green-600 mt-1">{{ number_format($campaign->total_conversions) }}</p>
                            <p class="text-xs text-gray-400">تحويلات</p>
                        </div>
                    </div>

                    @php
                        $myProducts = $campaign->products->filter(fn($p) => $p->vendorListing !== null);
                    @endphp
                    @if($myProducts->isNotEmpty())
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <p class="text-xs text-gray-400 mb-2">منتجاتك في الحملة:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($myProducts as $cp)
                                    <span class="text-xs bg-blue-50 text-blue-700 border border-blue-100 rounded-lg px-2 py-1">
                                        {{ $cp->vendorListing?->product?->name_en ?? '—' }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mt-3 flex justify-end">
                        <a href="{{ route('partner.marketer-campaigns.show', $campaign) }}"
                           class="text-xs font-semibold text-blue-600 hover:underline">عرض التفاصيل →</a>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $campaigns->links() }}</div>
    @endif

</div>

@endsection
