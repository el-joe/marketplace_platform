@props(['status'])

@php
    $map = [
        'placed' => ['color' => 'bg-blue-100 text-blue-700', 'label' => 'جديد'],
        'confirmed' => ['color' => 'bg-indigo-100 text-indigo-700', 'label' => 'مؤكد'],
        'processing' => ['color' => 'bg-purple-100 text-purple-700', 'label' => 'جارٍ التجهيز'],
        'ready_to_ship' => ['color' => 'bg-cyan-100 text-cyan-700', 'label' => 'جاهز للشحن'],
        'shipped' => ['color' => 'bg-yellow-100 text-yellow-700', 'label' => 'تم الشحن'],
        'out_for_delivery' => ['color' => 'bg-orange-100 text-orange-700', 'label' => 'في التوصيل'],
        'delivered' => ['color' => 'bg-green-100 text-green-700', 'label' => 'تم التسليم'],
        'completed' => ['color' => 'bg-green-100 text-green-800', 'label' => 'مكتمل'],
        'cancelled' => ['color' => 'bg-red-100 text-red-700', 'label' => 'ملغى'],
        'return_requested' => ['color' => 'bg-pink-100 text-pink-700', 'label' => 'طلب إرجاع'],
        'returned' => ['color' => 'bg-gray-100 text-gray-700', 'label' => 'مرتجع'],
        'refunded' => ['color' => 'bg-gray-100 text-gray-600', 'label' => 'مسترجع'],
    ];
    $entry = $map[$status] ?? ['color' => 'bg-gray-100 text-gray-600', 'label' => $status];
@endphp

<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $entry['color'] }}">
    {{ $entry['label'] }}
</span>