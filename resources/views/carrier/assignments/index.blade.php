@extends('layouts.carrier')

@section('title', 'الطلبات')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-black text-gray-900">طلبات التوصيل</h1>
    <p class="text-sm text-gray-500 mt-0.5">جميع الطلبات المعيّنة لمناديب شركتك — بما فيها غير المقبولة.</p>
</div>

{{-- Filters --}}
<form method="GET" class="mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1">الحالة</label>
        <select name="status" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">الكل</option>
            @foreach(['assigned'=>'معيّن','accepted'=>'مقبول','picked_up'=>'محمول','delivered'=>'مسلّم','failed'=>'فاشل'] as $val=>$label)
            <option value="{{ $val }}" @selected(request('status')===$val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    @if($assignments->isEmpty())
    <div class="py-16 text-center text-gray-400 text-sm">لا توجد طلبات.</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-6 py-3 text-right font-semibold">رقم الطلب</th>
                    <th class="px-6 py-3 text-right font-semibold">المندوب</th>
                    <th class="px-6 py-3 text-right font-semibold">الحالة</th>
                    <th class="px-6 py-3 text-right font-semibold">تاريخ التعيين</th>
                    @if(auth('shipping_supervisor')->user()->hasPermission('assign_orders'))
                    <th class="px-6 py-3 text-right font-semibold">إعادة تعيين</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($assignments as $a)
                @php
                    $colors = ['assigned'=>'amber','accepted'=>'blue','picked_up'=>'purple','delivered'=>'emerald','failed'=>'red'];
                    $labels = ['assigned'=>'معيّن','accepted'=>'مقبول','picked_up'=>'محمول','delivered'=>'مسلّم','failed'=>'فاشل'];
                    $c = $colors[$a->status] ?? 'gray';
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-mono text-xs text-gray-600">{{ substr($a->id, 0, 8) }}…</td>
                    <td class="px-6 py-3 font-medium text-gray-900">{{ $a->agent?->name ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $c }}-100 text-{{ $c }}-700">
                            {{ $labels[$a->status] ?? $a->status }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-gray-500 text-xs">{{ $a->assigned_at?->format('Y-m-d H:i') }}</td>
                    @if(auth('shipping_supervisor')->user()->hasPermission('assign_orders'))
                    <td class="px-6 py-3">
                        @if(in_array($a->status, ['assigned', 'accepted']))
                        <form method="POST" action="{{ route('carrier.assignments.reassign', $a->id) }}"
                              class="flex items-center gap-2">
                            @csrf
                            <select name="agent_id"
                                    class="border border-gray-300 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" @selected($agent->id === $a->agent_id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                            <button class="text-indigo-600 hover:underline text-xs font-medium">تعيين</button>
                        </form>
                        @endif
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $assignments->links() }}
    </div>
    @endif
</div>

@endsection
