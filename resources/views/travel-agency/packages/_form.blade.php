@php $pkg = $package ?? null; @endphp

{{-- Validation errors --}}
@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 space-y-1">
    @foreach($errors->all() as $error)
    <p>• {{ $error }}</p>
    @endforeach
</div>
@endif

{{-- Title (bilingual) --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">عنوان الباقة</h3>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">العنوان بالعربية *</label>
        <input type="text" name="title_ar" value="{{ old('title_ar', $pkg?->title_ar) }}" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        @error('title_ar') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Title in English *</label>
        <input type="text" name="title_en" value="{{ old('title_en', $pkg?->title_en) }}" required dir="ltr"
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        @error('title_en') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Destination + Dates --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">تفاصيل الرحلة</h3>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">الدولة المقصودة *</label>
            <input type="text" name="destination_country" value="{{ old('destination_country', $pkg?->destination_country) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">المدينة</label>
            <input type="text" name="destination_city" value="{{ old('destination_city', $pkg?->destination_city) }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ المغادرة *</label>
            <input type="date" name="departure_date" value="{{ old('departure_date', $pkg?->departure_date?->toDateString()) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ العودة *</label>
            <input type="date" name="return_date" value="{{ old('return_date', $pkg?->return_date?->toDateString()) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">عدد الأيام *</label>
            <input type="number" name="duration_days" value="{{ old('duration_days', $pkg?->duration_days) }}" min="1" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">عدد الليالي *</label>
            <input type="number" name="duration_nights" value="{{ old('duration_nights', $pkg?->duration_nights) }}" min="0" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
    </div>
</div>

{{-- Pricing + Seats --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">السعر والمقاعد</h3>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">السعر (فلس/هللة) *</label>
            <input type="number" name="price_cents" value="{{ old('price_cents', $pkg?->price_cents) }}" min="1" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400"
                   placeholder="e.g. 250000 = 2500.00">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">العملة *</label>
            <input type="text" name="currency" value="{{ old('currency', $pkg?->currency ?? auth()->user()?->country?->currency_code ?? '') }}" maxlength="3" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">عدد المقاعد المتاحة</label>
            <input type="number" name="available_seats" value="{{ old('available_seats', $pkg?->available_seats) }}" min="1"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400"
                   placeholder="اتركه فارغاً لعدم التحديد">
        </div>
    </div>
</div>

{{-- Inclusions --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">ما يشمله السعر</h3>
    <p class="text-xs text-gray-500">حدد ما يشمله السعر</p>
    @php
    $inclusionOptions = ['flights' => 'تذاكر طيران', 'hotel' => 'إقامة فندقية', 'meals' => 'وجبات', 'tours' => 'جولات سياحية', 'visa' => 'تأشيرة', 'insurance' => 'تأمين سفر', 'transfers' => 'مواصلات'];
    $selected = old('inclusions', $pkg?->inclusions ?? []);
    @endphp
    <div class="grid grid-cols-3 gap-3">
        @foreach($inclusionOptions as $value => $label)
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="inclusions[]" value="{{ $value }}"
                   {{ in_array($value, $selected) ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-gray-300 text-blue-500">
            <span class="text-sm text-gray-700">{{ $label }}</span>
        </label>
        @endforeach
    </div>
</div>

{{-- Descriptions --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">وصف الباقة</h3>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">الوصف بالعربية</label>
        <textarea name="description_ar" rows="4"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">{{ old('description_ar', $pkg?->description_ar) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Description in English</label>
        <textarea name="description_en" rows="4" dir="ltr"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">{{ old('description_en', $pkg?->description_en) }}</textarea>
    </div>
</div>

{{-- Media Upload --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">الصور والفيديو</h3>

    @if($pkg && $pkg->media->count())
    <div class="grid grid-cols-4 gap-3 mb-4">
        @foreach($pkg->media as $m)
        <div class="relative group">
            @if($m->media_type === 'image')
            <img src="{{ $m->url() }}" class="rounded-lg h-24 w-full object-cover border border-gray-200">
            @else
            <video src="{{ $m->url() }}" class="rounded-lg h-24 w-full object-cover border border-gray-200"></video>
            @endif
            <form method="POST" action="{{ route('travel-agency.packages.media.destroy', [$pkg, $m]) }}"
                  class="absolute top-1 left-1 hidden group-hover:block">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center leading-none">×</button>
            </form>
        </div>
        @endforeach
    </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">رفع ملفات (صور / فيديو) — بحد أقصى 10 ملفات</label>
        <input type="file" name="media[]" multiple accept="image/*,video/mp4,video/quicktime"
               class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100">
        <p class="mt-1 text-xs text-gray-400">أنواع مقبولة: JPG, PNG, WEBP, MP4, MOV — الحد الأقصى 50MB لكل ملف</p>
    </div>
</div>
