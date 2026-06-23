@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="bg-gray-950 py-24" id="fulfillment">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <span class="inline-block bg-yellow-400/10 border border-yellow-400/30 text-yellow-400
                         text-sm font-semibold px-4 py-1.5 rounded-full mb-4">
                {{ $isAr ? 'الشحن والتوصيل' : 'Shipping & Fulfillment' }}
            </span>
            <h2 class="text-3xl sm:text-4xl font-black text-white max-w-3xl mx-auto">
                {{ $isAr ? 'خيارات تنفيذ مرنة تناسبك' : 'Flexible Fulfillment Options That Fit You' }}
            </h2>
        </div>

        <div class="grid md:grid-cols-2 gap-6">

            {{-- Option 1: Fulfilled by Noon --}}
            <div class="relative group bg-gradient-to-br from-yellow-400/10 via-gray-900 to-gray-900
                        border border-yellow-400/30 hover:border-yellow-400/60 rounded-3xl p-8
                        transition-all duration-300 hover:shadow-xl hover:shadow-yellow-400/10">

                <div class="absolute top-0 {{ $isAr ? 'left-0' : 'right-0' }} w-32 h-32 rounded-full
                            bg-yellow-400/5 blur-3xl"></div>

                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 bg-yellow-400/20 text-yellow-400
                            text-xs font-bold px-3 py-1.5 rounded-full mb-6">
                    <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full"></span>
                    {{ $isAr ? 'موصى به' : 'Recommended' }}
                </div>

                <div class="text-5xl mb-4">🏭</div>

                <h3 class="text-2xl font-black text-white mb-3">
                    {{ $isAr ? 'التنفيذ من نون (FBN)' : 'Fulfilled by Noon (FBN)' }}
                </h3>
                <p class="text-gray-400 leading-relaxed mb-6">
                    {{ $isAr
    ? 'أرسل مخزونك إلى مستودعات نون ونحن نتكفل بالتخزين والتعبئة والشحن وخدمة العملاء بالكامل.'
    : 'Send your inventory to Noon warehouses and we handle storage, packing, shipping, and full customer service.' }}
                </p>

                <ul class="space-y-3">
                    @foreach([
                                            [$isAr ? 'تخزين مجاني لأول ٩٠ يوماً' : 'Free storage for first 90 days'],
                                            [$isAr ? 'توصيل خلال ٢٤ ساعة' : 'Delivery within 24 hours'],
                                            [$isAr ? 'إدارة المرتجعات تلقائياً' : 'Automated returns management'],
                                            [$isAr ? 'خدمة عملاء متخصصة' : 'Dedicated customer service'],
                                            [$isAr ? 'أولوية في نتائج البحث' : 'Priority in search results'],
                                        ] as $feat)
                                        <li class="flex items-center gap-3">
                                            <svg class="w-5 h-5 text-yellow-400 shrink-0" viewBox="0 0 24 24"
                                                 fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="20,6 9,17 4,12"/>
                                            </svg>
                          <span class="text-gray-300 text-sm">{{ $feat[0] }}</span>
                                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('portal.register') }}"
                   class="mt-8 w-full flex items-center justify-center gap-2 bg-yellow-400 hover:bg-yellow-300
                          text-gray-950 font-black py-3 px-6 rounded-xl transition-colors">
                    {{ $isAr ? 'ابدأ مع FBN' : 'Start with FBN' }}
                </a>
            </div>

            {{-- Option 2: Ship from Store --}}
            <div class="relative group bg-gray-900 border border-gray-800 hover:border-gray-600
                        rounded-3xl p-8 transition-all duration-300 hover:shadow-xl">

                <div class="inline-flex items-center gap-2 bg-blue-500/20 text-blue-400
                            text-xs font-bold px-3 py-1.5 rounded-full mb-6">
                    <span class="w-1.5 h-1.5 bg-blue-400 rounded-full"></span>
                    {{ $isAr ? 'تحكم كامل' : 'Full Control' }}
                </div>

                <div class="text-5xl mb-4">🏪</div>

                <h3 class="text-2xl font-black text-white mb-3">
                    {{ $isAr ? 'التنفيذ من البائع (SFS)' : 'Ship from Store (SFS)' }}
                </h3>
                <p class="text-gray-400 leading-relaxed mb-6">
                    {{ $isAr
    ? 'أنت تتحكم في المخزون والشحن من موقعك. مثالي للمنتجات الفريدة أو الكبيرة الحجم أو القابلة للتلف.'
    : 'You control inventory and shipping from your location. Ideal for unique, large, or perishable products.' }}
                </p>

                 <ul class="space-y-3">
                    @foreach([
                            [$isAr ? 'لا رسوم تخزين' : 'No storage fees'],
                            [$isAr ? 'مرونة في إدارة المخزون' : 'Flexible inventory management'],
                            [$isAr ? 'تحكم في عملية الشحن' : 'Control over shipping process'],
                            [$isAr ? 'مناسب لجميع أنواع المنتجات' : 'Suitable for all product types'],
                            [$isAr ? 'دمج مع شركاء الشحن' : 'Integration with shipping partners'],
                        ] as $feat)
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-blue-400 shrink-0" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20,6 9,17 4,12"/>
                            </svg>
                            <span class="text-gray-300 text-sm">{{ $feat[0] }}</span>
                        </li>
                    @endforeach
                </ul>

                <a href="{{ route('portal.register') }}"
                   class="mt-8 w-full flex items-center justify-center gap-2 bg-gray-800 hover:bg-gray-700
                          text-white font-bold py-3 px-6 rounded-xl border border-gray-700 transition-colors">
                    {{ $isAr ? 'ابدأ مع SFS' : 'Start with SFS' }}
                </a>
            </div>

        </div>

        {{-- Comparison table --}}
        <div class="mt-12 overflow-x-auto">
            <table class="w-full text-sm border-collapse">
             <thead>                 <trclass="border-b border-gray-800">
                    <th class="py-3 px-4 {{ $isAr ? 'text-right' : 'text-left' }} text-gray-400 font-medium">
                        {{ $isAr ? 'الميزة' : 'Feature' }}
                    </th>
                        <th class="py-3 px-4 text-center text-yellow-400 font-bold">FBN</th>
                        <th class="py-3 px-4 text-center text-blue-400 font-bold">SFS</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rows = [
                            [$isAr ? 'رسوم التخزين' : 'Storage Fees', 'بعد ٩٠ يوماً / After 90 days', '❌'],
                            [$isAr ? 'سرعة التوصيل' : 'Delivery Speed', '⚡ 24h', '🕐 1-3 days'],
                            [$isAr ? 'إدارة الشحن' : 'Shipping Management', '✅ نون / Noon', '✅ البائع / Seller'],
                            [$isAr ? 'خدمة العملاء' : 'Customer Service', '✅ نون / Noon', '✅ البائع / Seller'],
                            [$isAr ? 'مكانة البحث' : 'Search Ranking', '⬆️ أعلى / Higher', '📊 عادي / Normal'],
                        ];
                    @endphp
                    @foreach($rows as $row)
                        <tr class="border-b border-gray-800/50 hover:bg-gray-900/50">
                            <td class="py-3 px-4 text-gray-300">{{ $row[0] }}</td>
                            <td class="py-3 px-4 text-center text-gray-300">{{ $row[1] }}</td>
                            <td class="py-3 px-4 text-center text-gray-300">{{ $row[2] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</section>
