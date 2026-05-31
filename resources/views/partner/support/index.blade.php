@extends('layouts.partner')

@section('title', 'الدعم الفني والنزاعات')
@section('page-title', 'الدعم الفني')

@section('content')
    @php
        $statusLabels = [
            'open' => ['label' => 'مفتوحة', 'color' => 'bg-blue-100 text-blue-700'],
            'in_progress' => ['label' => 'قيد المعالجة', 'color' => 'bg-yellow-100 text-yellow-700'],
            'waiting_customer' => ['label' => 'بانتظار ردك', 'color' => 'bg-amber-100 text-amber-700'],
            'resolved' => ['label' => 'محلولة', 'color' => 'bg-green-100 text-green-700'],
            'closed' => ['label' => 'مغلقة', 'color' => 'bg-gray-100 text-gray-500'],
        ];
        $priorityLabels = [
            'low' => ['label' => 'منخفضة', 'color' => 'bg-gray-100 text-gray-600'],
            'normal' => ['label' => 'عادية', 'color' => 'bg-blue-100 text-blue-600'],
            'high' => ['label' => 'عالية', 'color' => 'bg-orange-100 text-orange-700'],
            'urgent' => ['label' => 'عاجلة', 'color' => 'bg-red-100 text-red-700'],
        ];
        $disputeStatusLabels = [
            'open' => ['label' => 'مفتوح', 'color' => 'bg-blue-100 text-blue-700'],
            'seller_responded' => ['label' => 'رددت عليه', 'color' => 'bg-indigo-100 text-indigo-700'],
            'under_review' => ['label' => 'قيد المراجعة', 'color' => 'bg-yellow-100 text-yellow-700'],
            'escalated' => ['label' => 'مُصعَّد', 'color' => 'bg-orange-100 text-orange-700'],
            'resolved' => ['label' => 'محلول', 'color' => 'bg-green-100 text-green-700'],
            'closed' => ['label' => 'مغلق', 'color' => 'bg-gray-100 text-gray-500'],
        ];
        $disputeReasonLabels = [
            'item_not_received' => 'المنتج لم يصل',
            'item_damaged' => 'المنتج تالف',
            'item_not_as_described' => 'المنتج لا يطابق الوصف',
            'counterfeit' => 'منتج مزيف',
            'wrong_item' => 'منتج خاطئ',
            'quality_issue' => 'مشكلة في الجودة',
            'seller_unresponsive' => 'البائع لا يرد',
            'refund_not_received' => 'الاسترداد لم يصل',
            'other' => 'أخرى',
        ];
    @endphp
    <div class="px-4 py-6 sm:px-6 lg:px-8"
        x-data="{ tab: '{{ request('tab', 'tickets') }}', ticketFilter: 'all', disputeFilter: 'all' }">

        {{-- Page header --}}
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">الدعم الفني والنزاعات</h1>
                <p class="mt-1 text-sm text-gray-500">تذاكر الدعم الخاصة بك والنزاعات المتعلقة بطلباتك</p>
            </div>
            <a href="{{ route('partner.support.tickets.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                فتح تذكرة جديدة
            </a>
        </div>

        {{-- Tabs --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex gap-x-6">
                <button type="button" @click="tab = 'tickets'" :class="tab === 'tickets'
                        ? 'border-primary-600 text-primary-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors">
                    تذاكر الدعم
                    @if ($tickets->where('status', 'waiting_customer')->count() > 0)
                        <span
                            class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                            {{ $tickets->where('status', 'waiting_customer')->count() }}
                        </span>
                    @endif
                </button>
                <button type="button" @click="tab = 'disputes'" :class="tab === 'disputes'
                        ? 'border-primary-600 text-primary-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="flex items-center gap-2 whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors">
                    النزاعات
                    @if ($disputes->whereIn('status', ['open', 'under_review', 'escalated'])->count() > 0)
                        <span
                            class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                            {{ $disputes->whereIn('status', ['open', 'under_review', 'escalated'])->count() }}
                        </span>
                    @endif
                </button>
            </nav>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════════ --}}
        {{-- Tab: Support Tickets --}}
        {{-- ══════════════════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'tickets'" x-cloak>

            {{-- Status filter pills --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach (['all' => 'الكل', 'open' => 'مفتوحة', 'waiting_customer' => 'بانتظار ردك', 'in_progress' => 'قيد المعالجة', 'resolved' => 'محلولة', 'closed' => 'مغلقة'] as $val => $lbl)
                    <button type="button" @click="ticketFilter = '{{ $val }}'" :class="ticketFilter === '{{ $val }}'
                                ? 'bg-primary-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-50'"
                        class="rounded-full border border-gray-200 px-3 py-1 text-xs font-medium transition-colors">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">رقم
                                التذكرة</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                الموضوع</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">
                                الفئة</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                الحالة</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                الأولوية</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                التاريخ</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($tickets as $ticket)
                            @php
                                $statusCfg = $statusLabels[$ticket->status] ?? ['label' => $ticket->status, 'color' => 'bg-gray-100 text-gray-600'];
                                $priorityCfg = $priorityLabels[$ticket->priority] ?? ['label' => $ticket->priority, 'color' => 'bg-gray-100 text-gray-600'];
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors"
                                x-show="ticketFilter === 'all' || ticketFilter === '{{ $ticket->status }}'" x-cloak>
                                <td class="px-5 py-4">
                                    <span
                                        class="font-mono text-xs font-semibold text-gray-700">{{ $ticket->ticket_number }}</span>
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $ticket->subject }}</p>
                                </td>
                                <td class="px-5 py-4 hidden md:table-cell">
                                    <span class="text-xs text-gray-500">{{ str_replace('_', ' ', $ticket->category) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusCfg['color'] }}">
                                        {{ $statusCfg['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 hidden sm:table-cell">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $priorityCfg['color'] }}">
                                        {{ $priorityCfg['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 hidden lg:table-cell">
                                    <span class="text-xs text-gray-400">{{ $ticket->created_at->format('d/m/Y') }}</span>
                                </td>
                                <td class="px-5 py-4 text-left">
                                    <a href="{{ route('partner.support.tickets.show', $ticket->ticket_number) }}"
                                        class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                        عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">
                                    لا توجد تذاكر دعم حتى الآن —
                                    <a href="{{ route('partner.support.tickets.create') }}"
                                        class="text-primary-600 hover:underline">افتح تذكرة جديدة</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════════ --}}
        {{-- Tab: Disputes --}}
        {{-- ══════════════════════════════════════════════════════════════════════ --}}
        <div x-show="tab === 'disputes'" x-cloak>

            {{-- Status filter pills --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach (['all' => 'الكل', 'open' => 'مفتوح', 'seller_responded' => 'رددت عليه', 'under_review' => 'قيد المراجعة', 'escalated' => 'مُصعَّد', 'resolved' => 'محلول', 'closed' => 'مغلق'] as $val => $lbl)
                    <button type="button" @click="disputeFilter = '{{ $val }}'" :class="disputeFilter === '{{ $val }}'
                                ? 'bg-primary-600 text-white'
                                : 'bg-white text-gray-600 hover:bg-gray-50'"
                        class="rounded-full border border-gray-200 px-3 py-1 text-xs font-medium transition-colors">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">رقم
                                النزاع</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                السبب</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                الحالة</th>
                            <th
                                class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">
                                التاريخ</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($disputes as $dispute)
                            @php $dStatusCfg = $disputeStatusLabels[$dispute->status] ?? ['label' => $dispute->status, 'color' => 'bg-gray-100 text-gray-600']; @endphp
                            <tr class="hover:bg-gray-50 transition-colors"
                                x-show="disputeFilter === 'all' || disputeFilter === '{{ $dispute->status }}'" x-cloak>
                                <td class="px-5 py-4">
                                    <span
                                        class="font-mono text-xs font-semibold text-gray-700">{{ $dispute->dispute_number }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="text-sm text-gray-700">{{ $disputeReasonLabels[$dispute->reason] ?? $dispute->reason }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $dStatusCfg['color'] }}">
                                        {{ $dStatusCfg['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 hidden sm:table-cell">
                                    <span class="text-xs text-gray-400">{{ $dispute->created_at->format('d/m/Y') }}</span>
                                </td>
                                <td class="px-5 py-4 text-left">
                                    <a href="{{ route('partner.disputes.show', $dispute->dispute_number) }}"
                                        class="text-xs font-medium text-primary-600 hover:text-primary-800">
                                        عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">
                                    لا توجد نزاعات مسجلة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection