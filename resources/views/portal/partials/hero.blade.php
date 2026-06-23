@php $isAr = session('locale', 'ar') === 'ar'; @endphp

<section class="relative min-h-[90vh] flex items-center overflow-hidden bg-gray-950">

    {{-- Background gradient --}}
    <div class="absolute inset-0 bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950"></div>

    {{-- Decorative yellow glow --}}
    <div class="absolute top-0 {{ $isAr ? 'left-0' : 'right-0' }} w-1/2 h-full opacity-10"
        style="background: radial-gradient(ellipse at {{ $isAr ? '80% 30%' : '20% 30%' }}, #facc15 0%, transparent 70%);">
    </div>

    {{-- Geometric shapes --}}
    <div class="absolute bottom-0 {{ $isAr ? 'right-0' : 'left-0' }} w-96 h-96 opacity-5"
        style="background: radial-gradient(circle, #facc15, transparent 70%);"></div>

    {{-- Dotted grid overlay --}}
    <div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(circle, #ffffff 1px, transparent 1px);
                background-size: 40px 40px;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 w-full">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

            {{-- Text Content --}}
            <div class="{{ $isAr ? 'text-right' : 'text-left' }}">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 bg-yellow-400/10 border border-yellow-400/30
                            text-yellow-400 text-sm font-semibold px-4 py-1.5 rounded-full mb-6">
                    <span class="w-2 h-2 bg-yellow-400 rounded-full animate-pulse"></span>
                    {{ $isAr ? '٢٠٠٠+ بائع جديد شهرياً' : '2,000+ new sellers monthly' }}
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-6">
                    {{ $isAr ? 'ابدأ البيع' : 'Start Selling' }}<br>
                    <span class="text-yellow-400">{{ $isAr ? 'على نون' : 'on Noon' }}</span><br>
                    <span class="text-3xl sm:text-4xl lg:text-5xl text-gray-300 font-bold">
                        {{ $isAr ? 'اليوم!' : 'Today!' }}
                    </span>
                </h1>

                <p
                    class="text-gray-400 text-lg sm:text-xl leading-relaxed mb-10 max-w-xl {{ $isAr ? 'mr-auto' : 'ml-auto lg:ml-0' }}">
                    {{ $isAr
    ? 'انضم إلى أكثر من ٥٠,٠٠٠ بائع على المنصة الرائدة في الشرق الأوسط. وصل إلى ملايين العملاء وحقق أرباحاً استثنائية.'
    : 'Join over 50,000 sellers on the Middle East\'s leading marketplace. Reach millions of customers and grow your business.' }}
                </p>

                <div class="flex flex-wrap gap-4 {{ $isAr ? 'justify-start' : 'justify-start' }}">
                    <a href="{{ route('portal.register') }}" class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-gray-950
                              font-black text-lg px-8 py-4 rounded-full shadow-xl shadow-yellow-400/30
                              transition-all duration-200 hover:scale-105 hover:shadow-yellow-400/50">
                        {{ $isAr ? 'سجّل الآن' : 'Register Now' }}
                        <svg class="w-5 h-5 {{ $isAr ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5">
                            <path d="M5 12h14m-7-7 7 7-7 7" />
                        </svg>
                    </a>

                    <a href="{{ route('portal.how-it-works') }}" class="inline-flex items-center gap-2 border-2 border-gray-600 hover:border-yellow-400
                              text-gray-300 hover:text-yellow-400 font-bold text-lg px-8 py-4 rounded-full
                              transition-all duration-200">
                        {{ $isAr ? 'دليل البدء' : 'Getting Started Guide' }}
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polygon points="10,8 16,12 10,16" />
                        </svg>
                    </a>
                </div>

                {{-- Trust stats --}}
                <div class="mt-12 flex flex-wrap gap-8 {{ $isAr ? 'justify-start' : 'justify-start' }}">
                    @foreach([
                            ['٥٠ ألف+', '50K+', 'بائع نشط', 'Active Sellers'],
                            ['٢٠ مليون+', '20M+', 'عميل', 'Customers'],
                            ['٣ دول', '3 Countries', 'تشمل الإمارات والسعودية ومصر', 'UAE, KSA, Egypt'],
                        ] as $stat)
                        <div class="text-{{ $isAr ? 'right' : 'left' }}">
                            <div class="text-2xl font-black text-yellow-400">{{ $isAr ? $stat[0] : $stat[1] }}</div>
                            <div class="text-sm text-gray-400">{{ $isAr ? $stat[2] : $stat[3] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Hero visual --}}
            <div class="hidden lg:flex items-center justify-center">
                <div class="relative w-full max-w-lg">
                    {{-- Glow ring --}}
                    <div class="absolute inset-0 rounded-3xl bg-yellow-400/10 blur-3xl"></div>

                    {{-- Mock dashboard card --}}
                    <div class="relative bg-gray-900 border border-gray-800 rounded-3xl p-6 shadow-2xl">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                                <span class="text-xs text-gray-400">{{ $isAr ? 'متجرك نشط' : 'Store Active' }}</span>
                            </div>
                            <span class="text-xs text-gray-500">{{ $isAr ? 'اليوم' : 'Today' }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            @foreach([
                                    ['💰', $isAr ? 'المبيعات اليوم' : "Today's Sales", '12,450 د.إ'],
                                    ['📦', $isAr ? 'الطلبات' : 'Orders', '38'],
                                    ['⭐', $isAr ? 'التقييم' : 'Rating', '4.9 / 5'],
                                    ['👥', $isAr ? 'الزوار' : 'Visitors', '1,204'],
                                ] as $metric)
                                <div class="bg-gray-800 rounded-2xl p-4">
                                    <div class="text-2xl mb-1">{{ $metric[0] }}</div>
                                    <div class="text-xs text-gray-400">{{ $metric[1] }}</div>
                                    <div class="text-base font-bold text-white mt-1">{{ $metric[2] }}</div>
                                </div>
                               @endforeach
                        </div>

                        {{-- Mini chart bars --}}
                        <div class="bg-gray-800 rounded-2xl p-4">
                            <div class="text-xs text-gray-400 mb-3">{{ $isAr ? 'المبيعات (آخر ٧ أيام)' : 'Sales (Last 7 Days)' }}</div>
                            <div class="flex items-end gap-1.5 h-16">
                                @foreach([40, 65, 45, 80, 55, 90, 70] as $h)
                                    <div class="flex-1 bg-yellow-400 rounded-t opacity-80 hover:opacity-100 transition-opacity"
                                         style="height: {{ $h }}%"></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
             </div>

        </div>
    </div>
    {{-- Wave divider --}}
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
            <path d="M0 60 C360 0 1080 0 1440 60 L1440 60 L0 60 Z" fill="#030712"/>
        </svg>
    </div>

</section>
