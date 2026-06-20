@extends('layouts.partner')

@section('title', 'الأداء والتحليلات')
@section('page-title', 'الأداء والتحليلات')

@push('scripts')
    @vite('resources/js/partner/performance.js')
    <script>
        window.PERFORMANCE = {
            statsUrl: '{{ route('partner.performance.stats') }}',
        };
    </script>
@endpush

@section('content')
    <div class="px-4 py-6 sm:px-6 lg:px-8 space-y-6">

        {{-- Page header + period selector ──────────────────────────────────────────}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">الأداء والتحليلات</h1>
                <p class="mt-1 text-sm text-gray-500" id="period-label">جاري التحميل...</p>
            </div>

            {{-- Period tabs --}}
            <div class="flex flex-col gap-2 items-end">
                <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 shadow-sm gap-0.5" role="tablist">
                    @foreach (['week' => 'أسبوع', 'month' => 'شهر', 'quarter' => 'ربع سنة', 'custom' => 'مخصص'] as $key => $label)
                        <button type="button" role="tab" data-period="{{ $key }}"
                            class="period-tab rounded-lg px-3.5 py-1.5 text-sm font-medium text-gray-600 transition-all hover:bg-gray-100
                                       {{ $key === 'month' ? 'bg-primary-600 text-white shadow-sm hover:bg-primary-600' : '' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Custom date range (hidden by default) --}}
                <div id="custom-range-row" class="hidden flex items-center gap-2 flex-wrap">
                    <input type="date" id="custom-start"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                    <span class="text-gray-400">—</span>
                    <input type="date" id="custom-end"
                        class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" />
                    <button id="btn-apply-custom"
                        class="rounded-lg bg-primary-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-700 transition-colors">
                        تطبيق
                    </button>
                </div>
            </div>
        </div>

        {{-- KPI cards ─────────────────────────────────────────────────────────────}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            {{-- GMV --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">إجمالي المبيعات (GMV)</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-50">
                        <svg class="h-4 w-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 tabular-nums" id="kpi-gmv">
                    <span class="block h-7 w-28 animate-pulse rounded bg-gray-200"></span>
                </p>
            </div>

            {{-- Orders count --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">عدد الطلبات</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50">
                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 tabular-nums" id="kpi-orders">
                    <span class="block h-7 w-20 animate-pulse rounded bg-gray-200"></span>
                </p>
            </div>

            {{-- Avg order value --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">متوسط قيمة الطلب</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50">
                        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 tabular-nums" id="kpi-aov">
                    <span class="block h-7 w-24 animate-pulse rounded bg-gray-200"></span>
                </p>
            </div>

            {{-- SLA compliance --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">الالتزام بالمواعيد</p>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-50">
                        <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold tabular-nums" id="kpi-sla">
                    <span class="block h-7 w-16 animate-pulse rounded bg-gray-200"></span>
                </p>
            </div>
        </div>

        {{-- Revenue chart + Performance indicators ──────────────────────────────────}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Revenue line chart --}}
            <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">إيرادات المبيعات</h2>
                    <span id="chart-loading" class="text-xs text-gray-400 animate-pulse">جاري التحميل...</span>
                </div>
                <div class="relative h-56">
                    <canvas id="revenue-chart"></canvas>
                    <div id="revenue-chart-empty" class="hidden absolute inset-0 flex items-center justify-center">
                        <p class="text-sm text-gray-400">لا توجد بيانات للفترة المحددة</p>
                    </div>
                </div>
            </div>

            {{-- Performance indicators --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-gray-800 mb-4">مؤشرات الأداء</h2>
                <div class="space-y-4">

                    {{-- SLA compliance indicator --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-medium text-gray-600">الالتزام بمواعيد الشحن</span>
                            <span class="text-sm font-bold" id="ind-sla-value">—</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100">
                            <div id="ind-sla-bar" class="h-2 rounded-full transition-all duration-500 bg-gray-300"
                                style="width:0%"></div>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-400">
                            <span>90%</span><span>95%</span><span>100%</span>
                        </div>
                    </div>

                    {{-- Return rate indicator --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-medium text-gray-600">معدل الإرجاع</span>
                            <span class="text-sm font-bold" id="ind-return-value">—</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100">
                            <div id="ind-return-bar" class="h-2 rounded-full transition-all duration-500 bg-gray-300"
                                style="width:0%"></div>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-400">
                            <span>0%</span><span>5%</span><span>10%+</span>
                        </div>
                    </div>

                    {{-- Cancellation rate --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-medium text-gray-600">معدل الإلغاء</span>
                            <span class="text-sm font-bold" id="ind-cancel-value">—</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100">
                            <div id="ind-cancel-bar" class="h-2 rounded-full transition-all duration-500 bg-gray-300"
                                style="width:0%"></div>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-400">
                            <span>0%</span><span>5%</span><span>10%+</span>
                        </div>
                    </div>

                    {{-- Rating --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-medium text-gray-600">تقييم المتجر</span>
                            <span class="text-sm font-bold" id="ind-rating-value">—</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100">
                            <div id="ind-rating-bar" class="h-2 rounded-full transition-all duration-500 bg-gray-300"
                                style="width:0%"></div>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-400">
                            <span>3.5</span><span>4.0</span><span>5.0</span>
                        </div>
                    </div>

                    {{-- Active strikes --}}
                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5 flex items-center justify-between">
                        <span class="text-xs font-medium text-gray-600">المخالفات النشطة</span>
                        <span class="text-sm font-bold" id="ind-strikes">
                            <span class="block h-4 w-6 animate-pulse rounded bg-gray-200"></span>
                        </span>
                    </div>

                </div>
            </div>
        </div>

        {{-- Top products + Reviews distribution ─────────────────────────────────────}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Top 5 products --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-800">أفضل 5 منتجات</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-xs font-medium text-gray-500">#</th>
                                <th class="px-4 py-2.5 text-xs font-medium text-gray-500 text-right">المنتج</th>
                                <th class="px-4 py-2.5 text-xs font-medium text-gray-500 text-right">الإيرادات</th>
                                <th class="px-4 py-2.5 text-xs font-medium text-gray-500 text-right">وحدات</th>
                            </tr>
                        </thead>
                        <tbody id="top-products-body" class="divide-y divide-gray-100">
                            {{-- Skeleton rows --}}
                            @for ($i = 0; $i < 5; $i++)
                                <tr>
                                    <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3"><span class="block h-4 w-40 animate-pulse rounded bg-gray-200"></span>
                                    </td>
                                    <td class="px-4 py-3"><span class="block h-4 w-20 animate-pulse rounded bg-gray-200"></span>
                                    </td>
                                    <td class="px-4 py-3"><span class="block h-4 w-12 animate-pulse rounded bg-gray-200"></span>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Reviews distribution --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-sm font-semibold text-gray-800">توزيع التقييمات</h2>
                    <span class="text-xs text-gray-400" id="reviews-total">—</span>
                </div>
                <div class="relative h-56 mt-4">
                    <canvas id="reviews-chart"></canvas>
                    <div id="reviews-chart-empty" class="hidden absolute inset-0 flex items-center justify-center">
                        <p class="text-sm text-gray-400">لا توجد تقييمات في هذه الفترة</p>
                    </div>
                </div>
            </div>

        </div>

        {{-- Flash sale summary (only shows if > 0) ──────────────────────────────── --}}
        <div id="flash-summary-row" class="hidden">
            <div class="rounded-xl border border-purple-200 bg-purple-50 px-5 py-4 flex items-center gap-6 flex-wrap">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                    <span class="text-sm font-semibold text-purple-800">عروض الفلاش في هذه الفترة</span>
                </div>
                <div class="flex gap-6">
                    <div>
                        <p class="text-xs text-purple-600">وحدات مباعة</p>
                        <p class="text-base font-bold text-purple-900" id="flash-units">0</p>
                    </div>
                    <div>
                        <p class="text-xs text-purple-600">الإيرادات</p>
                        <p class="text-base font-bold text-purple-900" id="flash-revenue">0</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
