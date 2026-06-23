@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-gray-900 py-24" id="smart-tools">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <span class="inline-block bg-yellow-400/10 border border-yellow-400/30 text-yellow-400
                         text-sm font-semibold px-4 py-1.5 rounded-full mb-4">
                {{ $isAr ? 'الأدوات الذكية' : 'Smart Tools' }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-white">
                {{ $isAr ? 'نمّ أعمالك بذكاء' : 'Grow Your Business Smartly' }}
            </h2>
            <p class="mt-4 text-gray-400 text-lg max-w-2xl mx-auto">
                {{ $isAr
                    ? 'منظومة متكاملة من الأدوات المصممة لتعزيز مبيعاتك وتحسين تجربة عملائك.'
                    : 'A complete suite of tools designed to boost your sales and improve your customer experience.' }}
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">

            {{-- Ads Card --}}
            <div class="relative bg-gray-800 border border-gray-700 hover:border-yellow-400/40
                        rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1
                        hover:shadow-xl hover:shadow-yellow-400/5 group">
                <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-yellow-400/5 to-transparent
                            opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-yellow-400/20 rounded-2xl flex items-center justify-center
                                text-2xl mb-6">🎯</div>
                    <h3 class="text-xl font-black text-white mb-3">
                        {{ $isAr ? 'إعلانات تحقق نتائج' : 'Ads That Deliver Results' }}
                    </h3>
                    <p class="text-gray-400 leading-relaxed text-sm mb-6">
                        {{ $isAr
                            ? 'استخدم إعلانات نون الموجّهة لاستهداف العملاء المناسبين في الوقت المناسب وزيادة ظهور منتجاتك.'
                            : 'Use Noon\'s targeted ads to reach the right customers at the right time and boost your product visibility.' }}
                    </p>
                    <ul class="space-y-2">
                        @foreach([
                            [$isAr ? 'إعلانات مدفوعة بالنقرة' : 'Pay-per-click ads'],
                            [$isAr ? 'إعلانات العرض الموجّهة' : 'Targeted display ads'],
                            [$isAr ? 'ترويج المنتجات المميزة' : 'Featured product promotion'],
                        ] as $f)
                        <li class="flex items-center gap-2 text-sm text-gray-300">
                            <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full shrink-0"></span>
                            {{ $f[0] }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Cost Calculator Card --}}
            <div class="relative bg-gray-800 border border-gray-700 hover:border-blue-400/40
                        rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1
                        hover:shadow-xl hover:shadow-blue-400/5 group">
                <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-blue-400/5 to-transparent
                            opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-blue-500/20 rounded-2xl flex items-center justify-center
                                text-2xl mb-6">💰</div>
                    <h3 class="text-xl font-black text-white mb-3">
                        {{ $isAr ? 'اعرف تكاليفك' : 'Know Your Costs' }}
                    </h3>
                    <p class="text-gray-400 leading-relaxed text-sm mb-6">
                        {{ $isAr
                            ? 'حساب العمولات والرسوم والأرباح المتوقعة قبل نشر أي منتج لتضمن هامش ربحي جيد.'
                            : 'Calculate commissions, fees, and expected profits before listing any product to ensure a good profit margin.' }}
                    </p>

                    {{-- Mini fee calculator visual --}}
                    <div class="bg-gray-900/80 rounded-2xl p-4 space-y-2">
                        @foreach([
                            [$isAr ? 'سعر المنتج' : 'Product Price', '100 د.إ', 'AED 100'],
                            [$isAr ? 'عمولة نون' : 'Noon Commission', '8%', '8%'],
                            [$isAr ? 'رسوم الشحن' : 'Shipping Fee', '5 د.إ', 'AED 5'],
                            [$isAr ? 'صافي الربح' : 'Net Profit', '87 د.إ ✓', 'AED 87 ✓'],
                        ] as $row)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-400">{{ $row[0] }}</span>
                            <span class="font-mono text-white">{{ $isAr ? $row[1] : $row[2] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Analytics Card --}}
            <div class="relative bg-gray-800 border border-gray-700 hover:border-green-400/40
                        rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1
                        hover:shadow-xl hover:shadow-green-400/5 group">
                <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-green-400/5 to-transparent
                            opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-green-500/20 rounded-2xl flex items-center justify-center
                                text-2xl mb-6">📊</div>
                    <h3 class="text-xl font-black text-white mb-3">
                        {{ $isAr ? 'توسع مع الرؤى' : 'Grow with Insights' }}
                    </h3>
                    <p class="text-gray-400 leading-relaxed text-sm mb-6">
                        {{ $isAr
                            ? 'احصل على تقارير مفصلة حول المبيعات والزوار وسلوك العملاء لاتخاذ قرارات قائمة على البيانات.'
                            : 'Get detailed reports on sales, visitors, and customer behavior to make data-driven decisions.' }}
                    </p>

                    {{-- Mini chart --}}
                    <div class="bg-gray-900/80 rounded-2xl p-4">
                        <div class="text-xs text-gray-400 mb-3">
                            {{ $isAr ? 'نمو المبيعات' : 'Sales Growth' }}
                        </div>
                        <div class="flex items-end gap-1 h-12">
                            @foreach([30, 45, 35, 60, 50, 75, 65, 90, 80, 100] as $h)
                            <div class="flex-1 rounded-t transition-all hover:opacity-100 opacity-70"
                                 style="height: {{ $h }}%; background: linear-gradient(to top, #34d399, #6ee7b7)"></div>
                            @endforeach
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>{{ $isAr ? 'يناير' : 'Jan' }}</span>
                            <span class="text-green-400 font-bold">+{{ $isAr ? '٢٣٤٪' : '234%' }}</span>
                            <span>{{ $isAr ? 'أكتوبر' : 'Oct' }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Additional tools row --}}
        <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
            $extra = [
                ['🏷️', 'مدير الكوبونات', 'Coupon Manager'],
                ['📦', 'إدارة المخزون', 'Inventory Manager'],
                ['🔔', 'تنبيهات السعر', 'Price Alerts'],
                ['📱', 'تطبيق البائع', 'Seller App'],
            ];
            @endphp
            @foreach($extra as $t)
            <div class="bg-gray-800/60 border border-gray-700/60 rounded-2xl p-5
                        flex flex-col items-center text-center gap-2 hover:border-gray-600 transition-colors">
                <span class="text-3xl">{{ $t[0] }}</span>
                <span class="text-sm font-semibold text-gray-300">
                    {{ $isAr ? $t[1] : $t[2] }}
                </span>
            </div>
            @endforeach
        </div>

    </div>
</section>
