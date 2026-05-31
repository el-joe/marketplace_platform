@extends('layouts.partner')

@section('title', 'فتح تذكرة دعم جديدة')
@section('page-title', 'تذكرة جديدة')

@push('scripts')
    @vite('resources/js/partner/support.js')
    <script>
        window.SUPPORT = {
            csrf: '{{ csrf_token() }}',
            storeUrl: '{{ route('partner.support.tickets.store') }}',
            indexUrl: '{{ route('partner.support.tickets.index') }}',
        };
    </script>
@endpush

@section('content')
    <div class="px-4 py-6 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <div class="mb-5 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('partner.support.tickets.index') }}" class="hover:text-gray-700">الدعم الفني</a>
            <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="text-gray-800 font-medium">تذكرة جديدة</span>
        </div>

        <div class="max-w-2xl bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <h1 class="text-lg font-bold text-gray-900 mb-6">فتح تذكرة دعم جديدة</h1>

            <form id="form-create-ticket" novalidate>
                <div class="space-y-5">

                    {{-- Subject --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            موضوع التذكرة <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="subject" maxlength="255" placeholder="وصف مختصر للمشكلة"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>

                    {{-- Category + Priority --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                الفئة <span class="text-red-500">*</span>
                            </label>
                            <select name="category"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                <option value="">اختر الفئة...</option>
                                <option value="order_issue">مشكلة في طلب</option>
                                <option value="payment_issue">مشكلة في الدفع</option>
                                <option value="payout">المدفوعات والأرباح</option>
                                <option value="catalog">المنتجات والكتالوج</option>
                                <option value="account">الحساب والصلاحيات</option>
                                <option value="technical">مشكلة تقنية</option>
                                <option value="policy">السياسات والشروط</option>
                                <option value="product_inquiry">استفسار عن منتج</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">الأولوية</label>
                            <select name="priority"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                                <option value="low">منخفضة</option>
                                <option value="normal" selected>عادية</option>
                                <option value="high">عالية</option>
                            </select>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            وصف المشكلة <span class="text-red-500">*</span>
                        </label>
                        <textarea name="description" rows="6" maxlength="5000"
                            placeholder="اشرح المشكلة بالتفصيل — كلما كانت التفاصيل أكثر، كان حلّها أسرع"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 resize-y"></textarea>
                        <p class="mt-1 text-xs text-gray-400">بحد أقصى 5000 حرف</p>
                    </div>

                </div>

                {{-- Error container --}}
                <div id="ticket-error"
                    class="hidden mt-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('partner.support.tickets.index') }}"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        إلغاء
                    </a>
                    <button type="submit" id="btn-create-ticket"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        فتح التذكرة
                    </button>
                </div>
            </form>
        </div>

    </div>
@endsection