@extends('layouts.partner')

@section('title', 'الملف الشخصي وإعدادات المتجر')
@section('page-title', 'الملف الشخصي')

@push('scripts')
    @vite('resources/js/partner/profile.js')
    <script>
        window.PROFILE = {
            csrf:              '{{ csrf_token() }}',
            updateStoreUrl:    '{{ route('partner.profile.update-store') }}',
            updatePasswordUrl: '{{ route('partner.profile.update-password') }}',
            uploadDocumentUrl: '{{ route('partner.documents.upload') }}',
            isOwner:           {{ $admin->isOwner() ? 'true' : 'false' }},
        };
    </script>
@endpush

@section('content')
@php
$docTypeLabels = [
    'business_license'  => 'رخصة تجارية',
    'tax_certificate'   => 'شهادة ضريبية',
    'owner_id'          => 'هوية المالك',
    'bank_proof'        => 'إثبات حساب بنكي',
    'vat_registration'  => 'تسجيل ضريبة القيمة المضافة',
];
$businessTypeLabels = [
    'individual'  => 'فرد',
    'company'     => 'شركة',
    'sole'        => 'مؤسسة فردية',
];
@endphp
<div class="px-4 py-6 sm:px-6 lg:px-8"
     x-data="{ tab: '{{ request('tab', 'store') }}' }">

    {{-- Page header --}}
    <div class="mb-6 flex items-center gap-4">
        {{-- Avatar --}}
        <div class="flex-shrink-0 h-14 w-14 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-xl select-none">
            {{ mb_substr($vendor->store_name, 0, 1) }}
        </div>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $vendor->store_name }}</h1>
            <p class="text-sm text-gray-500">
                @if ($vendor->country)
                    {{ $vendor->country->flag_emoji ?? '' }} {{ $vendor->country->name_ar ?? $vendor->country->name }}
                    &nbsp;·&nbsp;
                @endif
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                    {{ $vendor->global_status === 'active' ? 'bg-green-100 text-green-700' :
                       ($vendor->global_status === 'suspended' ? 'bg-red-100 text-red-700' :
                       'bg-yellow-100 text-yellow-700') }}">
                    {{ $vendor->global_status === 'active' ? 'نشط' :
                       ($vendor->global_status === 'suspended' ? 'موقوف' :
                       ($vendor->global_status === 'rejected' ? 'مرفوض' : 'قيد المراجعة')) }}
                </span>
            </p>
        </div>
    </div>

    {{-- Tabs nav --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-x-6 overflow-x-auto">
            @foreach ([
                'store'    => 'معلومات المتجر',
                'documents'=> 'المستندات',
                'password' => 'تغيير كلمة المرور',
            ] as $key => $label)
                <button type="button"
                    @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'border-primary-600 text-primary-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium transition-colors">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Store info                                                        --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'store'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Editable store fields --}}
            <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-5">بيانات المتجر</h2>

                @if (! $admin->isOwner())
                    <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700">
                        يمكن للمالك فقط تعديل بيانات المتجر.
                    </div>
                @endif

                <form id="form-store" novalidate>
                    <div class="space-y-4">
                        {{-- Store name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">اسم المتجر <span class="text-red-500">*</span></label>
                            <input type="text" name="store_name"
                                value="{{ old('store_name', $vendor->store_name) }}"
                                {{ ! $admin->isOwner() ? 'disabled' : '' }}
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-400">
                        </div>

                        {{-- Store description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">وصف المتجر</label>
                            <textarea name="store_description" rows="3"
                                {{ ! $admin->isOwner() ? 'disabled' : '' }}
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-400">{{ old('store_description', $vendor->store_description) }}</textarea>
                        </div>

                        {{-- Contact email --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">البريد الإلكتروني للتواصل <span class="text-red-500">*</span></label>
                                <input type="email" name="contact_email"
                                    value="{{ old('contact_email', $vendor->contact_email) }}"
                                    {{ ! $admin->isOwner() ? 'disabled' : '' }}
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-400">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">رقم الهاتف <span class="text-red-500">*</span></label>
                                <input type="text" name="contact_phone"
                                    value="{{ old('contact_phone', $vendor->contact_phone) }}"
                                    {{ ! $admin->isOwner() ? 'disabled' : '' }}
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-400">
                            </div>
                        </div>

                        {{-- WhatsApp --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">رقم واتساب</label>
                            <input type="text" name="whatsapp_number"
                                value="{{ old('whatsapp_number', $vendor->whatsapp_number) }}"
                                {{ ! $admin->isOwner() ? 'disabled' : '' }}
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 disabled:bg-gray-50 disabled:text-gray-400">
                        </div>
                    </div>

                    @if ($admin->isOwner())
                        <div class="mt-6 flex justify-end">
                            <button type="submit" id="btn-save-store"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                حفظ التغييرات
                            </button>
                        </div>
                    @endif
                </form>
            </div>

            {{-- Read-only business info --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 h-fit">
                <h2 class="text-base font-semibold text-gray-800 mb-5">معلومات الأعمال</h2>
                <dl class="space-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 mb-0.5">البلد</dt>
                        <dd class="font-medium text-gray-900">
                            @if ($vendor->country)
                                {{ $vendor->country->flag_emoji ?? '' }} {{ $vendor->country->name_ar ?? $vendor->country->name }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 mb-0.5">نوع الأعمال</dt>
                        <dd class="font-medium text-gray-900">
                            {{ $businessTypeLabels[$vendor->business_type] ?? ($vendor->business_type ?? '—') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 mb-0.5">رقم السجل التجاري</dt>
                        <dd class="font-medium text-gray-900 font-mono text-xs">
                            {{ $vendor->business_registration_number ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 mb-0.5">البريد الإلكتروني الرئيسي</dt>
                        <dd class="font-medium text-gray-900">{{ $vendor->email }}</dd>
                    </div>
                    <div class="pt-3 border-t border-gray-100">
                        <p class="text-xs text-gray-400">لتعديل هذه البيانات يرجى التواصل مع فريق الدعم.</p>
                    </div>
                </dl>

                {{-- Bank accounts summary --}}
                @if ($bankAccounts->isNotEmpty())
                    <h3 class="text-sm font-semibold text-gray-700 mt-6 mb-3">الحسابات البنكية</h3>
                    <ul class="space-y-2">
                        @foreach ($bankAccounts as $acct)
                            <li class="flex items-center justify-between text-xs rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                                <span class="font-medium text-gray-800">{{ $acct->bank_name }}</span>
                                <span class="text-gray-500 font-mono">•••• {{ substr($acct->account_number ?? $acct->iban ?? '', -4) }}</span>
                                @if ($acct->is_primary)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">رئيسي</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Documents                                                         --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'documents'" x-cloak>
        <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-700">
            يُرجى التأكد من رفع جميع الوثائق المطلوبة بصيغ PDF أو JPG أو PNG بحد أقصى 5 ميغابايت لكل ملف.
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($docTypeLabels as $type => $label)
                @php $doc = $documents->get($type); @endphp
                <div class="bg-white rounded-2xl border {{ $doc && ($doc->isExpired() || $doc->status === 'rejected') ? 'border-red-300 border-dashed' : 'border-gray-200' }} shadow-sm p-5 flex flex-col gap-3"
                     id="doc-card-{{ $type }}">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <svg class="w-5 h-5 flex-shrink-0 {{ $doc ? 'text-primary-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-sm font-semibold text-gray-800 truncate">{{ $label }}</span>
                        </div>
                        {{-- Status badge --}}
                        @if ($doc)
                            @php
                                $status = $doc->isExpired() ? 'expired' : $doc->status;
                                $badgeCfg = match($status) {
                                    'approved', 'verified' => ['bg-green-100 text-green-700', 'موثَّق'],
                                    'pending'              => ['bg-yellow-100 text-yellow-700', 'قيد المراجعة'],
                                    'rejected'             => ['bg-red-100 text-red-700', 'مرفوض'],
                                    'expired'              => ['bg-red-100 text-red-600', 'منتهي الصلاحية'],
                                    default                => ['bg-gray-100 text-gray-600', $status],
                                };
                            @endphp
                            <span class="flex-shrink-0 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeCfg[0] }}">
                                {{ $badgeCfg[1] }}
                            </span>
                        @else
                            <span class="flex-shrink-0 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-500">
                                لم يُرفع
                            </span>
                        @endif
                    </div>

                    {{-- Dates --}}
                    @if ($doc)
                        <div class="text-xs text-gray-500 space-y-1">
                            <div class="flex justify-between">
                                <span>تاريخ الرفع</span>
                                <span class="font-medium text-gray-700">{{ $doc->created_at->format('d/m/Y') }}</span>
                            </div>
                            @if ($doc->expires_at)
                                <div class="flex justify-between">
                                    <span>تاريخ الانتهاء</span>
                                    <span class="font-medium {{ $doc->isExpired() ? 'text-red-600' : 'text-gray-700' }}">
                                        {{ $doc->expires_at->format('d/m/Y') }}
                                    </span>
                                </div>
                            @endif
                            @if ($doc->rejection_reason)
                                <p class="mt-1 text-red-600 text-xs">{{ $doc->rejection_reason }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="mt-auto flex items-center gap-2 pt-2">
                        @if ($doc && $doc->file_path)
                            <a href="{{ asset('storage/' . $doc->file_path) }}"
                               target="_blank"
                               class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                معاينة
                            </a>
                        @endif

                        {{-- Upload button --}}
                        <label class="cursor-pointer inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            {{ $doc ? 'إعادة رفع' : 'رفع الملف' }}
                            <input type="file"
                                   class="hidden doc-upload-input"
                                   data-type="{{ $type }}"
                                   accept=".pdf,.jpg,.jpeg,.png">
                        </label>

                        {{-- Upload progress indicator --}}
                        <span class="doc-upload-status-{{ $type }} hidden text-xs text-gray-400">
                            جارٍ الرفع...
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: Password                                                          --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'password'" x-cloak>
        <div class="max-w-lg bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-5">تغيير كلمة المرور</h2>
            <form id="form-password" novalidate>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور الحالية <span class="text-red-500">*</span></label>
                        <input type="password" name="current_password" autocomplete="current-password"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">كلمة المرور الجديدة <span class="text-red-500">*</span></label>
                        <input type="password" name="password" autocomplete="new-password"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        <p class="mt-1 text-xs text-gray-400">8 أحرف على الأقل</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">تأكيد كلمة المرور <span class="text-red-500">*</span></label>
                        <input type="password" name="password_confirmation" autocomplete="new-password"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" id="btn-save-password"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        تغيير كلمة المرور
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
