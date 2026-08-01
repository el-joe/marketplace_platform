@extends('layouts.admin')

@section('title', 'إعدادات الماركتر')

@section('content')

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">إعدادات الماركتر</h1>
        <p class="text-sm text-gray-500 mt-0.5">إدارة الكوميشن، رسوم الإنفلوينسر، وإعدادات العينات لكل فئة ودولة.</p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ─── 1) Marketer Commission per Category & Country ────────────────────── --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">كوميشن الماركتر لكل فئة ودولة</h2>

        {{-- Filter --}}
        <div class="mb-4 flex items-center gap-3">
            <label for="commission-country-filter" class="text-sm font-medium text-gray-700">تصفية حسب الدولة</label>
            <select id="commission-country-filter" onchange="document.querySelectorAll('[data-commission-country]').forEach(el => el.style.display = (this.value === '' || el.dataset.commissionCountry === this.value) ? '' : 'none')"
                class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">كل الدول</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}">{{ $country->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">الفئة</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">الدولة</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">كوميشن الإنفلوينسر</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">كوميشن الأفيليت</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">العملة</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($countries as $country)
                        @foreach ($categories as $category)
                            @php
                                $setting = optional($commissions->get($country->id))->firstWhere('category_id', $category->id);
                            @endphp
                            <tr data-commission-country="{{ $country->id }}">
                                <form method="POST" action="{{ route('admin.marketer-settings.update-commission') }}" class="contents">
                                    @csrf
                                    <input type="hidden" name="category_id" value="{{ $category->id }}">
                                    <input type="hidden" name="country_id" value="{{ $country->id }}">
                                    <td class="px-3 py-2 text-gray-900">
                                        {{ str_repeat('— ', $category->depth ?? 0) }}{{ $category->name_ar ?? $category->name_en }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">{{ $country->name_ar }}</td>
                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="1" name="influencer_commission_amount"
                                            value="{{ old('influencer_commission_amount', $setting->influencer_commission_amount ?? 0) }}"
                                            class="w-24 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" min="0" step="1" name="affiliate_commission_amount"
                                            value="{{ old('affiliate_commission_amount', $setting->affiliate_commission_amount ?? 0) }}"
                                            class="w-24 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" name="currency" maxlength="3"
                                            value="{{ old('currency', $setting->currency ?? $country->currency_code) }}"
                                            class="w-16 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 uppercase">
                                    </td>
                                    <td class="px-3 py-2">
                                        <button type="submit"
                                            class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700">
                                            حفظ
                                        </button>
                                    </td>
                                </form>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── 2) Influencer Fee per Country ─────────────────────────────────────── --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">رسوم الإنفلوينسر لكل دولة</h2>
        <p class="text-xs text-gray-500 mb-4">ملاحظة: الأفيليت مجاني ولا توجد عليه رسوم.</p>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">الدولة</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">رسوم الإنفلوينسر</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">العملة</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($countries as $country)
                        @php $fee = $influencerFees->get($country->id); @endphp
                        <tr>
                            <form method="POST" action="{{ route('admin.marketer-settings.update-fee') }}" class="contents">
                                @csrf
                                <input type="hidden" name="country_id" value="{{ $country->id }}">
                                <td class="px-3 py-2 text-gray-900">{{ $country->name_ar }}</td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" step="1" name="fee_per_influencer"
                                        value="{{ old('fee_per_influencer', $fee->fee_per_influencer ?? 0) }}"
                                        class="w-24 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="currency" maxlength="3"
                                        value="{{ old('currency', $fee->currency ?? $country->currency_code) }}"
                                        class="w-16 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500 uppercase">
                                </td>
                                <td class="px-3 py-2">
                                    <button type="submit"
                                        class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700">
                                        حفظ
                                    </button>
                                </td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── 3) Sample & Stock Settings per Category ───────────────────────────── --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">إعدادات العينات والمخزون لكل فئة</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">الفئة</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">عينات الإنفلوينسر</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">عينات الأفيليت</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">عينات المنصة</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500">الحد الأدنى للمخزون للحملة</th>
                        <th class="px-3 py-2 text-right font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($categories as $category)
                        <tr>
                            <form method="POST" action="{{ route('admin.marketer-settings.update-category') }}" class="contents">
                                @csrf
                                <input type="hidden" name="category_id" value="{{ $category->id }}">
                                <td class="px-3 py-2 text-gray-900">
                                    {{ str_repeat('— ', $category->depth ?? 0) }}{{ $category->name_ar ?? $category->name_en }}
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" step="1" name="influencer_sample_qty"
                                        value="{{ old('influencer_sample_qty', $category->influencer_sample_qty ?? 0) }}"
                                        class="w-20 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" step="1" name="affiliate_sample_qty"
                                        value="{{ old('affiliate_sample_qty', $category->affiliate_sample_qty ?? 0) }}"
                                        class="w-20 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" step="1" name="platform_sample_qty"
                                        value="{{ old('platform_sample_qty', $category->platform_sample_qty ?? 0) }}"
                                        class="w-20 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" min="0" step="1" name="min_stock_for_campaign"
                                        value="{{ old('min_stock_for_campaign', $category->min_stock_for_campaign ?? 0) }}"
                                        class="w-24 rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500">
                                </td>
                                <td class="px-3 py-2">
                                    <button type="submit"
                                        class="inline-flex items-center rounded-md bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700">
                                        حفظ
                                    </button>
                                </td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
