@extends('layouts.partner')

@section('title', 'الطلب ' . $subOrder->sub_order_number)
@section('page-title', 'تفاصيل الطلب')

@push('scripts')
    @vite('resources/js/partner/orders.js')
    <script>
        window.ORDER_DETAIL = {
            confirmUrl: '{{ route('partner.orders.confirm', $subOrder->sub_order_number) }}',
            shipUrl: '{{ route('partner.orders.ship', $subOrder->sub_order_number) }}',
            outForDeliveryUrl: '{{ route('partner.orders.out-for-delivery', $subOrder->sub_order_number) }}',
            deliverUrl: '{{ route('partner.orders.deliver', $subOrder->sub_order_number) }}',
            cancelUrl: '{{ route('partner.orders.cancel', $subOrder->sub_order_number) }}',
            csrf: '{{ csrf_token() }}',
            status: '{{ $subOrder->status }}',
        };
    </script>
@endpush

@section('content')

    @php
        $order = $subOrder->order;
        $address = $order->shipping_address_snapshot ?? [];
        $customer = $order->customer;

        // Mask customer name: first name + last initial
        $maskName = function (?string $name): string {
            if (!$name)
                return '—';
            $parts = explode(' ', trim($name));
            if (count($parts) === 1)
                return $parts[0];
            return $parts[0] . ' ' . mb_substr(end($parts), 0, 1) . '.';
        };

        $maskedName = $maskName($customer?->name);
        $maskedPhone = $customer?->phone
            ? preg_replace('/(\d{3})\d+(\d{2})/', '$1•••••$2', $customer->phone)
            : '—';

        $isUrgent = $subOrder->sla_ship_deadline &&
            now()->addHours(2)->gt($subOrder->sla_ship_deadline) &&
            !in_array($subOrder->status, ['shipped', 'delivered', 'completed', 'cancelled']);
        $isPast = $subOrder->sla_ship_deadline && now()->gt($subOrder->sla_ship_deadline);

        $statusLabels = [
            'placed' => 'معلق',
            'confirmed' => 'مؤكد',
            'processing' => 'جارٍ التجهيز',
            'packed' => 'جاهز للشحن',
            'shipped' => 'تم الشحن',
            'out_for_delivery' => 'في التوصيل',
            'delivered' => 'تم التسليم',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغى',
            'return_requested' => 'طلب إرجاع',
            'returned' => 'مرتجع',
        ];
    @endphp

    {{-- Back link --}}
    <div class="mb-4">
        <a href="{{ route('partner.orders.index') }}"
            class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <x-heroicon name="arrow-right" class="w-4 h-4" />
            العودة للطلبات
        </a>
    </div>

    {{-- Main grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- ── LEFT COLUMN ── --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Order Items --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">عناصر الطلب</h3>
                <div class="divide-y divide-gray-50">
                    @foreach($subOrder->items as $item)
                        @php
                            $snap = $item->product_snapshot ?? [];
                            $imgUrl = $snap['image_url'] ?? null;
                            $name = $snap['name_ar'] ?? $snap['name'] ?? 'منتج';
                            $variant = $snap['variant'] ?? null;
                        @endphp
                        <div class="flex items-start gap-4 py-4 first:pt-0 last:pb-0">
                            <div
                                class="w-14 h-14 rounded-xl border border-gray-100 bg-gray-50 overflow-hidden shrink-0 flex items-center justify-center">
                                @if($imgUrl)
                                    <img src="{{ $imgUrl }}" alt="{{ $name }}" class="w-full h-full object-cover">
                                @else
                                    <x-heroicon name="cube" class="w-6 h-6 text-gray-300" />
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-sm">{{ $name }}</p>
                                @if($variant)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $variant }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ $item->sku }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm text-gray-500">{{ $item->quantity }} ×
                                    {{ number_format($item->unit_price / 100, 2) }} {{ $currency }}</p>
                                <p class="font-semibold text-gray-900 mt-0.5">{{ number_format($item->line_total / 100, 2) }}
                                    {{ $currency }}</p>
                                @if(($item->commission_amount ?? 0) > 0)
                                    <p class="text-xs text-red-500 mt-0.5">
                                        عمولة: −{{ number_format($item->commission_amount / 100, 2) }} {{ $currency }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Delivery Address (full, visible in detail only) --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <x-heroicon name="map-pin" class="w-4 h-4 text-gray-400" />
                    عنوان التوصيل
                </h3>
                <div class="text-sm text-gray-700 space-y-1">
                    <p class="font-medium">{{ $address['name'] ?? $maskedName }}</p>
                    @if(!empty($address['address_line_1']))
                        <p>{{ $address['address_line_1'] }}</p>
                    @endif
                    @if(!empty($address['address_line_2']))
                        <p>{{ $address['address_line_2'] }}</p>
                    @endif
                    <p>
                        {{ implode(', ', array_filter([
        $address['area'] ?? null,
        $address['city'] ?? null,
        $address['country'] ?? null,
    ])) }}
                    </p>
                    @if(!empty($address['zip_code']))
                        <p class="text-gray-400 text-xs">{{ $address['zip_code'] }}</p>
                    @endif
                </div>

                {{-- Masked customer contact --}}
                <div class="mt-4 pt-4 border-t border-gray-50 flex items-center gap-6">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <x-heroicon name="user" class="w-4 h-4 text-gray-400" />
                        <span>{{ $maskedName }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <x-heroicon name="phone" class="w-4 h-4 text-gray-400" />
                        <span dir="ltr" class="font-mono select-all">{{ $maskedPhone }}</span>
                    </div>
                </div>
            </div>

            {{-- Customer notes --}}
            @if($order->customer_notes)
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                    <div class="flex items-start gap-2">
                        <x-heroicon name="chat-bubble-left" class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" />
                        <div>
                            <p class="text-xs font-semibold text-amber-700 mb-1">ملاحظات العميل</p>
                            <p class="text-sm text-amber-900">{{ $order->customer_notes }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Status history timeline --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">سجل الحالة</h3>
                <ol class="relative border-s border-gray-200 space-y-5 pe-4">
                    @foreach($subOrder->statusHistories as $history)
                        <li class="ms-4">
                            <div class="absolute w-2.5 h-2.5 bg-yellow-400 rounded-full -start-1.5 border border-white mt-1.5">
                            </div>
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-800">
                                        {{ $statusLabels[$history->to_status] ?? $history->to_status }}
                                    </p>
                                    @if($history->reason)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $history->reason }}</p>
                                    @endif
                                </div>
                                <time class="text-xs text-gray-400 shrink-0 ms-4">
                                    {{ $history->created_at->format('M d, H:i') }}
                                </time>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Tracking events (if shipped) --}}
            @if($subOrder->shipments->isNotEmpty())
                @foreach($subOrder->shipments as $shipment)
                    @if($shipment->trackingEvents->isNotEmpty())
                        <div class="bg-white rounded-2xl border border-gray-200 p-6">
                            <h3 class="font-semibold text-gray-800 mb-1 flex items-center gap-2">
                                <x-heroicon name="truck" class="w-4 h-4 text-gray-400" />
                                تتبع الشحنة
                            </h3>
                            <p class="text-xs text-gray-400 mb-4 font-mono">{{ $shipment->tracking_number }}</p>
                            <ol class="relative border-s border-gray-200 space-y-4 pe-4">
                                @foreach($shipment->trackingEvents->sortByDesc('occurred_at') as $event)
                                    <li class="ms-4">
                                        <div class="absolute w-2 h-2 bg-gray-300 rounded-full -start-1 mt-1.5 border border-white"></div>
                                        <p class="text-sm text-gray-700">{{ $event->description }}</p>
                                        <time class="text-xs text-gray-400">{{ $event->occurred_at?->format('M d, H:i') }}</time>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                @endforeach
            @endif

        </div>

        {{-- ── RIGHT SIDEBAR ── --}}
        <div class="lg:col-span-4 space-y-4">

            {{-- Order Summary card --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h4 class="font-semibold text-gray-800 mb-3 text-sm">ملخص الطلب</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>رقم الطلب</span>
                        <span class="font-mono text-xs text-gray-800">{{ $subOrder->sub_order_number }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>المجموع الفرعي</span>
                        <span class="font-medium">{{ number_format($subOrder->subtotal / 100, 2) }} {{ $currency }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>عمولة المنصة</span>
                        <span class="text-red-500">- {{ number_format($subOrder->platform_commission / 100, 2) }}
                            {{ $currency }}</span>
                    </div>
                    @if($subOrder->gateway_fee > 0)
                        <div class="flex justify-between text-gray-600">
                            <span class="inline-flex items-center gap-1">
                                رسوم معالجة الدفع
                                <span class="cursor-help text-gray-400" title="يتحمل التاجر رسوم بوابة الدفع وفقًا لسياسة المنصة.">
                                    <x-heroicon name="information-circle" class="w-3.5 h-3.5" />
                                </span>
                            </span>
                            <span class="text-red-500">- {{ number_format($subOrder->gateway_fee / 100, 2) }}
                                {{ $currency }}</span>
                        </div>
                    @endif
                    <div class="border-t border-gray-100 pt-2 flex justify-between font-semibold text-gray-900">
                        <span>صافي المدفوعات</span>
                        <span class="text-green-600">{{ number_format($subOrder->vendor_payout / 100, 2) }}
                            {{ $currency }}</span>
                    </div>
                </div>
            </div>

            @if(!$subOrder->cod_remittance_confirmed && $order->payment_method === 'cod')
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
                    <strong>تنبيه:</strong> مبلغ تحويل هذا الطلب ({{ number_format($subOrder->vendor_payout / 100, 2) }} {{ $currency }})
                    في انتظار تأكيد تحصيل الدفع النقدي (COD) من المندوب.
                    سيتم إضافته لدورة المدفوعات التالية عند التأكيد.
                </div>
            @endif

            {{-- SLA card --}}
            @if($subOrder->sla_ship_deadline && !in_array($subOrder->status, ['shipped', 'delivered', 'completed', 'cancelled']))
                <div @class([
                    'rounded-2xl border p-5',
                    'bg-red-50 border-red-200' => $isPast,
                    'bg-orange-50 border-orange-200' => !$isPast && $isUrgent,
                    'bg-white border-gray-200' => !$isPast && !$isUrgent,
                ])>
                    <div class="flex items-center gap-2 mb-2">
                        <x-heroicon name="clock" @class(['w-4 h-4', 'text-red-500' => $isPast, 'text-orange-500' => !$isPast && $isUrgent, 'text-gray-400' => !$isPast && !$isUrgent]) />
                        <h4
                            class="text-sm font-semibold {{ $isPast ? 'text-red-700' : ($isUrgent ? 'text-orange-700' : 'text-gray-700') }}">
                            موعد الشحن
                        </h4>
                    </div>
                    <p
                        class="{{ $isPast ? 'text-red-800 font-bold' : ($isUrgent ? 'text-orange-700 font-semibold' : 'text-gray-800') }} text-sm">
                        {{ $subOrder->sla_ship_deadline->diffForHumans() }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $subOrder->sla_ship_deadline->format('d M Y, H:i') }}
                    </p>
                </div>
            @endif

            {{-- Actions card --}}
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h4 class="font-semibold text-gray-800 mb-3 text-sm">الإجراءات</h4>
                <div class="space-y-2">

                    {{-- Current status badge --}}
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-gray-500">الحالة الحالية</span>
                        <x-status-badge :status="$subOrder->status" />
                    </div>

                    @if($subOrder->status === 'placed')
                        <button id="btn-confirm"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                            <x-heroicon name="check-circle" class="w-4 h-4" />
                            تأكيد الطلب
                        </button>
                    @endif

                    @if(in_array($subOrder->status, ['placed', 'confirmed', 'processing', 'packed']))
                        @if($subOrder->shipping_method_id)
                            <button id="btn-ship"
                                class="w-full bg-green-600 hover:bg-green-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                                <x-heroicon name="truck" class="w-4 h-4" />
                                شحن الطلب
                            </button>
                        @else
                            <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2.5 text-center">
                                بانتظار تعيين طريقة الشحن من قبل الإدارة
                            </p>
                        @endif
                    @endif

                    @if($subOrder->status === 'shipped')
                        <button id="btn-out-for-delivery"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                            <x-heroicon name="truck" class="w-4 h-4" />
                            تحديث: في التوصيل
                        </button>
                    @endif

                    @if(in_array($subOrder->status, ['shipped', 'out_for_delivery']))
                        <button id="btn-deliver"
                            class="w-full bg-green-700 hover:bg-green-800 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                            <x-heroicon name="check-badge" class="w-4 h-4" />
                            تأكيد التسليم
                        </button>
                    @endif

                    @if(!in_array($subOrder->status, ['shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'return_requested', 'returned']))
                        <button id="btn-cancel"
                            class="w-full bg-white border border-red-200 hover:bg-red-50 text-red-600 text-sm font-semibold py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                            <x-heroicon name="x-circle" class="w-4 h-4" />
                            إلغاء الطلب
                        </button>
                    @endif

                    @if(in_array($subOrder->status, ['delivered', 'completed', 'cancelled']))
                        <p class="text-xs text-gray-400 text-center py-1">لا توجد إجراءات متاحة</p>
                    @endif

                </div>
            </div>

            {{-- Carrier / Tracking (if shipped) --}}
            @if($subOrder->status !== 'placed' && $subOrder->tracking_number)
                <div class="bg-white rounded-2xl border border-gray-200 p-5">
                    <h4 class="font-semibold text-gray-800 mb-3 text-sm flex items-center gap-2">
                        <x-heroicon name="truck" class="w-4 h-4 text-gray-400" />
                        معلومات الشحن
                    </h4>
                    <div class="space-y-2 text-sm">
                        @if($subOrder->shippingMethod)
                            <div class="flex justify-between text-gray-600">
                                <span>طريقة الشحن</span>
                                <span class="font-medium">{{ $subOrder->shippingMethod->name }}</span>
                            </div>
                        @endif
                        @if($subOrder->carrier)
                            <div class="flex justify-between text-gray-600">
                                <span>شركة الشحن</span>
                                <span class="font-medium">{{ $subOrder->carrier->name }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-gray-600">
                            <span>رقم التتبع</span>
                            <span class="font-mono text-xs text-gray-800 select-all">{{ $subOrder->tracking_number }}</span>
                        </div>
                        @if($subOrder->estimated_delivery_date)
                            <div class="flex justify-between text-gray-600">
                                <span>التسليم المتوقع</span>
                                <span class="font-medium">{{ $subOrder->estimated_delivery_date->format('d M Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>

    </div>

    {{-- ── SHIP MODAL ── --}}
    <div id="ship-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">تأكيد الشحن</h3>
                <button id="ship-modal-close" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <x-heroicon name="x-mark" class="w-5 h-5" />
                </button>
            </div>
            <form id="ship-form" class="p-6 space-y-4">
                @if($subOrder->shippingMethod)
                    <div class="text-sm text-gray-600 bg-gray-50 rounded-xl px-3 py-2.5">
                        طريقة الشحن المعينة: <span class="font-medium text-gray-800">{{ $subOrder->shippingMethod->name }}</span>
                        @if($subOrder->carrier)
                            عبر <span class="font-medium text-gray-800">{{ $subOrder->carrier->name }}</span>
                        @endif
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">رقم التتبع</label>
                    <input type="text" name="tracking_number" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40"
                        placeholder="TRACK123456">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">تاريخ التسليم المتوقع</label>
                    <input type="date" name="estimated_delivery_date" required min="{{ date('Y-m-d') }}"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400/40">
                </div>
                <div id="ship-error" class="hidden text-sm text-red-600 bg-red-50 rounded-lg p-3"></div>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 rounded-xl transition-colors text-sm">
                        تأكيد الشحن
                    </button>
                    <button type="button" id="ship-cancel-btn"
                        class="flex-1 border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold py-2.5 rounded-xl transition-colors text-sm">
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── CANCEL MODAL ── --}}
    <div id="cancel-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-red-700">إلغاء الطلب</h3>
                <button id="cancel-modal-close" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <x-heroicon name="x-mark" class="w-5 h-5" />
                </button>
            </div>
            <form id="cancel-form" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">سبب الإلغاء</label>
                    <select name="reason" required
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400/40">
                        <option value="">اختر السبب...</option>
                        <option value="out_of_stock">نفاد المخزون</option>
                        <option value="unable_to_fulfill">غير قادر على التنفيذ</option>
                        <option value="duplicate_order">طلب مكرر</option>
                        <option value="customer_request">طلب العميل</option>
                        <option value="pricing_error">خطأ في السعر</option>
                        <option value="other">سبب آخر</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">ملاحظات إضافية <span
                            class="text-gray-400">(اختياري)</span></label>
                    <textarea name="reason_notes" rows="3"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400/40 resize-none"
                        placeholder="أي تفاصيل إضافية..."></textarea>
                </div>
                <div id="cancel-error" class="hidden text-sm text-red-600 bg-red-50 rounded-lg p-3"></div>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-xl transition-colors text-sm">
                        تأكيد الإلغاء
                    </button>
                    <button type="button" id="cancel-cancel-btn"
                        class="flex-1 border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold py-2.5 rounded-xl transition-colors text-sm">
                        تراجع
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection