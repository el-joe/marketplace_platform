@extends('layouts.partner')

@section('title', 'إرجاع ' . $return->return_number)
@section('page-title', 'تفاصيل طلب الإرجاع')

@push('scripts')
<script>
    window.RETURN_CONFIG = {
        csrf: '{{ csrf_token() }}',
        messagesUrl: '/api/vendor/v1/returns/{{ $return->return_number }}/messages',
        status: '{{ $return->status }}',
    };
</script>
@vite('resources/js/partner/returns.js')
@endpush

@section('content')

@php
    $statusMap = [
        'requested'        => ['bg-yellow-100 text-yellow-700',  'مطلوب'],
        'approved'         => ['bg-blue-100 text-blue-700',      'مقبول'],
        'rejected'         => ['bg-red-100 text-red-700',        'مرفوض'],
        'awaiting_pickup'  => ['bg-amber-100 text-amber-700',    'بانتظار الاستلام'],
        'in_transit'       => ['bg-indigo-100 text-indigo-700',  'في الطريق'],
        'received'         => ['bg-cyan-100 text-cyan-700',      'تم الاستلام'],
        'inspecting'       => ['bg-purple-100 text-purple-700',  'قيد الفحص'],
        'completed'        => ['bg-green-100 text-green-700',    'مكتمل'],
        'cancelled'        => ['bg-gray-100 text-gray-500',      'ملغى'],
    ];
    $reasonMap = [
        'changed_mind'     => 'غيّر رأيه',
        'wrong_item'       => 'منتج خاطئ',
        'defective'        => 'معيب',
        'damaged'          => 'تالف',
        'not_as_described' => 'غير مطابق للوصف',
        'size_issue'       => 'مشكلة في المقاس',
        'quality_issue'    => 'جودة رديئة',
        'arrived_late'     => 'وصل متأخراً',
        'other'            => 'أخرى',
    ];
    $typeMap = [
        'refund'       => 'استرداد مبلغ',
        'exchange'     => 'استبدال',
        'store_credit' => 'رصيد متجر',
    ];
    $inspectionMap = [
        'good'         => ['bg-green-100 text-green-700',   'سليم'],
        'damaged'      => ['bg-red-100 text-red-700',       'تالف'],
        'missing'      => ['bg-orange-100 text-orange-700', 'ناقص'],
        'counterfeit'  => ['bg-red-100 text-red-800',       'مقلّد'],
    ];
    $liabilityMap = [
        'customer'  => 'العميل',
        'seller'    => 'البائع',
        'platform'  => 'المنصة',
        'carrier'   => 'شركة الشحن',
    ];
    $isFinal       = in_array($return->status, ['completed', 'cancelled']);
    $hasInspection = in_array($return->status, ['inspecting', 'completed', 'cancelled']);
    $orderMasked   = $return->order ? '****' . substr($return->order->order_number, -4) : null;
    $customerName  = trim(($return->customer->first_name ?? '') . ' ' . (isset($return->customer->last_name) ? strtoupper(substr($return->customer->last_name, 0, 1)) . '.' : ''));
    [$statusCls, $statusLabel] = $statusMap[$return->status] ?? ['bg-gray-100 text-gray-500', $return->status];
@endphp

{{-- Breadcrumb --}}
<div class="mb-4 flex items-center gap-2 text-sm text-gray-500">
    <a href="{{ route('partner.returns.index') }}" class="hover:text-gray-700">طلبات الإرجاع</a>
    <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="font-mono text-gray-800 font-medium">{{ $return->return_number }}</span>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

    {{-- LEFT COLUMN: detail cards --}}
    <div class="xl:col-span-2 space-y-4">

        {{-- Header --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <p class="text-xs text-gray-400 mb-1">رقم طلب الإرجاع</p>
                    <h1 class="text-lg font-bold font-mono text-gray-900">{{ $return->return_number }}</h1>
                    <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-500">
                        @if($orderMasked)
                            <span>طلب: <span class="font-mono">{{ $orderMasked }}</span></span>
                            <span>·</span>
                        @endif
                        @if($return->subOrder)
                            <span>طلب فرعي: <span class="font-mono">{{ $return->subOrder->sub_order_number }}</span></span>
                            <span>·</span>
                        @endif
                        <span>{{ $return->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
                <span class="flex-shrink-0 inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusCls }}">
                    {{ $statusLabel }}
                </span>
            </div>
        </div>

        {{-- Return details --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">تفاصيل الإرجاع</h2>
            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <dt class="text-xs text-gray-400 mb-0.5">العميل</dt>
                    <dd class="font-medium text-gray-800">{{ $customerName ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 mb-0.5">سبب الإرجاع</dt>
                    <dd class="font-medium text-gray-800">{{ $reasonMap[$return->reason] ?? $return->reason }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400 mb-0.5">نوع الإرجاع</dt>
                    <dd class="font-medium text-gray-800">{{ $typeMap[$return->return_type] ?? $return->return_type }}</dd>
                </div>
                @if($return->pickup_scheduled_at)
                    <div>
                        <dt class="text-xs text-gray-400 mb-0.5">موعد الاستلام</dt>
                        <dd class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($return->pickup_scheduled_at)->format('d/m/Y') }}</dd>
                    </div>
                @endif
                @if($return->received_at_warehouse_at)
                    <div>
                        <dt class="text-xs text-gray-400 mb-0.5">تاريخ الاستلام في المستودع</dt>
                        <dd class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($return->received_at_warehouse_at)->format('d/m/Y') }}</dd>
                    </div>
                @endif
                @if($return->rejection_reason)
                    <div class="col-span-2 sm:col-span-3">
                        <dt class="text-xs text-gray-400 mb-0.5">سبب الرفض</dt>
                        <dd class="font-medium text-red-700">{{ $return->rejection_reason }}</dd>
                    </div>
                @endif
                @if($return->reason_description)
                    <div class="col-span-2 sm:col-span-3">
                        <dt class="text-xs text-gray-400 mb-0.5">وصف السبب</dt>
                        <dd class="text-gray-700">{{ $return->reason_description }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Inspection result (shown only once status >= inspecting) --}}
        @if($hasInspection && $return->inspection_result)
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">نتيجة الفحص</h2>
                @php [$insCls, $insLabel] = $inspectionMap[$return->inspection_result] ?? ['bg-gray-100 text-gray-600', $return->inspection_result]; @endphp
                <div class="flex items-center gap-3 mb-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $insCls }}">
                        {{ $insLabel }}
                    </span>
                </div>
                @if($return->inspection_notes)
                    <p class="text-sm text-gray-700">{{ $return->inspection_notes }}</p>
                @endif
            </div>
        @endif

        {{-- Liability + refund (shown only when final) --}}
        @if($isFinal && ($return->liability || $return->refund_amount_cents))
            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">القرار النهائي</h2>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    @if($return->liability)
                        <div>
                            <dt class="text-xs text-gray-400 mb-0.5">المسؤولية</dt>
                            <dd class="font-medium text-gray-800">{{ $liabilityMap[$return->liability] ?? $return->liability }}</dd>
                        </div>
                    @endif
                    @if($return->refund_amount_cents)
                        <div>
                            <dt class="text-xs text-gray-400 mb-0.5">مبلغ الاسترداد</dt>
                            <dd class="font-bold text-green-700">{{ number_format($return->refund_amount_cents / 100, 2) }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif

        {{-- Items --}}
        @if($return->items->isNotEmpty())
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700">المنتجات المُرجَعة</h2>
                </div>
                <div class="divide-y divide-gray-50">
                    @foreach($return->items as $item)
                        @php
                            $snapshot = $item->orderItem?->product_snapshot ?? [];
                            $name  = $snapshot['name'] ?? $snapshot['title'] ?? 'منتج';
                            $image = $snapshot['image'] ?? null;
                            $condMap = ['new' => 'جديد', 'opened' => 'مفتوح', 'used' => 'مستعمل', 'damaged' => 'تالف'];
                        @endphp
                        <div class="flex items-center gap-4 px-5 py-3">
                            @if($image)
                                <img src="{{ $image }}" alt="{{ $name }}" class="w-12 h-12 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-300 text-xl">📦</div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">الكمية: {{ $item->quantity }}</p>
                            </div>
                            @if($item->condition_received)
                                <span class="flex-shrink-0 text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                    {{ $condMap[$item->condition_received] ?? $item->condition_received }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

    {{-- RIGHT COLUMN: message channel --}}
    <div class="xl:col-span-1">
        <div class="bg-white rounded-2xl border border-gray-200 flex flex-col" style="min-height: 520px;">

            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">المراسلات مع الإدارة</h2>
                <p class="text-xs text-gray-400 mt-0.5">تستخدم هذه القناة لتقديم الأدلة أو الإيضاحات للإدارة.</p>
            </div>

            {{-- Thread --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3" id="thread" style="max-height: 400px;">
                @forelse($return->messages as $msg)
                    @php $isMine = $msg->sender_role === 'seller'; @endphp
                    <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%]">
                            <p class="text-xs mb-1 {{ $isMine ? 'text-end' : 'text-start' }} text-gray-400">
                                {{ $isMine ? 'أنت' : ($msg->sender_role === 'customer' ? 'العميل' : 'الإدارة') }}
                                · {{ \Carbon\Carbon::parse($msg->created_at)->format('d/m H:i') }}
                            </p>
                            <div @class([
                                'rounded-2xl px-4 py-2.5 text-sm leading-relaxed shadow-sm',
                                'bg-primary-600 text-white rounded-tl-md' => $isMine,
                                'bg-gray-100 text-gray-800 rounded-tr-md' => !$isMine,
                            ])>
                                {!! nl2br(e($msg->message)) !!}
                                @if($msg->attachments->isNotEmpty())
                                    <div class="mt-2 space-y-1">
                                        @foreach($msg->attachments as $att)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::temporaryUrl($att->file_path, now()->addMinutes(30)) }}"
                                                target="_blank"
                                                class="flex items-center gap-1.5 text-xs underline {{ $isMine ? 'text-blue-100' : 'text-primary-600' }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                </svg>
                                                مرفق {{ $loop->iteration }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-xs text-gray-400" id="empty-thread">
                        لا توجد رسائل بعد. يمكنك إرسال أدلة أو إيضاحات للإدارة.
                    </div>
                @endforelse
            </div>

            {{-- Reply form --}}
            <div class="border-t border-gray-100 p-4">
                <form id="form-message" novalidate>
                    @csrf
                    <textarea id="msg-text" name="message" rows="3"
                        placeholder="اكتب رسالتك أو أضف أدلة للإدارة..."
                        class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-y mb-2"></textarea>

                    <div class="mb-3">
                        <label class="block text-xs text-gray-500 mb-1">مرفقات (اختياري، حتى 5 ملفات)</label>
                        <input type="file" id="msg-attachments" name="attachments[]" multiple
                            accept="image/*,.pdf,video/mp4,video/quicktime"
                            class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                    </div>

                    <div class="flex items-center justify-between">
                        <span id="msg-status" class="text-xs text-gray-400"></span>
                        <button type="submit" id="btn-send"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            إرسال
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

</div>

@endsection
