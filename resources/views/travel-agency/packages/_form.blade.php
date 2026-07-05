@php $pkg = $package ?? null; @endphp

{{-- Validation errors --}}
@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700 space-y-1">
    @foreach($errors->all() as $error)
    <p>• {{ $error }}</p>
    @endforeach
</div>
@endif

{{-- Contract File --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-3">
    <div class="flex items-center gap-2">
        <h3 class="font-bold text-gray-800">{{ __('travel.packages.package_contract') }}</h3>
        <span class="text-red-500 text-sm font-medium">*</span>
    </div>

    @if($pkg?->contract_file_path)
    <div class="flex items-center gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm">
        <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        <div class="flex-1 min-w-0">
            <p class="text-gray-700">{{ __('travel.packages.current_contract') }} <span class="font-medium">{{ $pkg->contract_file_original_name }}</span></p>
            @if($pkg->contract_uploaded_at)
            <p class="text-xs text-gray-400 mt-0.5">{{ __('travel.packages.uploaded_at') }}{{ $pkg->contract_uploaded_at->format('d M Y H:i') }}</p>
            @endif
        </div>
        <a href="{{ route('travel-agency.packages.contract.download', $pkg) }}"
           class="text-blue-600 hover:text-blue-800 font-medium shrink-0">{{ __('travel.packages.view') }}</a>
    </div>
    <p class="text-xs text-gray-400">{{ __('travel.packages.upload_new_contract') }}</p>
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ __('travel.packages.package_contract_pdf') }}{{ $pkg?->contract_file_path ? '' : ' *' }}
        </label>
        <input type="file" name="contract_file" accept="application/pdf"
               id="contract-file-input"
               {{ !$pkg?->contract_file_path ? 'required' : '' }}
               class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100">
        <p class="mt-1 text-xs text-gray-400">{{ __('travel.packages.pdf_only') }}</p>
        @error('contract_file') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Title (bilingual) --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">{{ __('travel.packages.package_title') }}</h3>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.title_ar') }} *</label>
        <input type="text" name="title_ar" value="{{ old('title_ar', $pkg?->title_ar) }}" required
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        @error('title_ar') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.title_en') }} *</label>
        <input type="text" name="title_en" value="{{ old('title_en', $pkg?->title_en) }}" required dir="ltr"
               class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        @error('title_en') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Destination + Dates --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">{{ __('travel.packages.trip_details') }}</h3>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.destination_travel_country') }} *</label>
            <select name="destination_travel_country_id" id="travel-country-select" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
                <option value="">— {{ __('travel.packages.destination_travel_country') }} —</option>
                @foreach($travelCountries as $c)
                    <option value="{{ $c->id }}"
                        {{ old('destination_travel_country_id', $pkg?->destination_travel_country_id) == $c->id ? 'selected' : '' }}>
                        {{ $c->flag_emoji }} {{ $c->name_en }}
                    </option>
                @endforeach
            </select>
            @error('destination_travel_country_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.travel_city') }} *</label>
            <select name="destination_travel_city_id" id="travel-city-select"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400 disabled:bg-gray-100 disabled:text-gray-400"
                    {{ old('destination_travel_country_id', $pkg?->destination_travel_country_id) ? '' : 'disabled' }}>
                <option value="">{{ __('travel.packages.travel_city_placeholder') }}</option>
            </select>
            @error('destination_travel_city_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.departure_date') }} *</label>
            <input type="date" name="departure_date" value="{{ old('departure_date', $pkg?->departure_date?->toDateString()) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.return_date') }} *</label>
            <input type="date" name="return_date" value="{{ old('return_date', $pkg?->return_date?->toDateString()) }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.duration_days') }} *</label>
            <input type="number" name="duration_days" value="{{ old('duration_days', $pkg?->duration_days) }}" min="1" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.duration_nights') }} *</label>
            <input type="number" name="duration_nights" value="{{ old('duration_nights', $pkg?->duration_nights) }}" min="0" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
        </div>
    </div>
</div>

{{-- Pricing + Seats --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">{{ __('travel.packages.pricing_seats') }}</h3>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.price_cents') }} *</label>
            <input type="number" name="price_cents" value="{{ old('price_cents', $pkg?->price_cents) }}" min="1" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400"
                   placeholder="e.g. 250000 = 2500.00">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.currency') }} *</label>
            @php $selectedCurrency = old('currency', $pkg?->currency ?? auth()->guard('travel_agency')->user()?->country?->currency_code ?? ''); @endphp
            <select name="currency" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">
                <option value="">— {{ __('travel.packages.currency_placeholder') }} —</option>
                @foreach($currencies as $cur)
                    <option value="{{ $cur->code }}" {{ $selectedCurrency === $cur->code ? 'selected' : '' }}>
                        {{ $cur->code }} — {{ $cur->name }} ({{ $cur->symbol }})
                    </option>
                @endforeach
            </select>
            @error('currency') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.available_seats_no') }}</label>
            <input type="number" name="available_seats" value="{{ old('available_seats', $pkg?->available_seats) }}" min="1"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400"
                   placeholder="{{ __('travel.packages.available_seats_no_placeholder') }}">
        </div>
    </div>
</div>

{{-- Inclusions --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">{{ __('travel.packages.inclusions') }}</h3>
    <p class="text-xs text-gray-500">{{ __('travel.packages.inclusions_description') }}</p>
    @php
    $inclusionOptions = ['flights' => __('travel.packages.flights'), 'hotel' => __('travel.packages.hotel'), 'meals' => __('travel.packages.meals'), 'tours' => __('travel.packages.tours'), 'visa' => __('travel.packages.visa'), 'insurance' => __('travel.packages.insurance'), 'transfers' => __('travel.packages.transfers')];
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
    <h3 class="font-bold text-gray-800">{{ __('travel.packages.description') }}</h3>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.description_ar') }}</label>
        <textarea name="description_ar" rows="4"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">{{ old('description_ar', $pkg?->description_ar) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.description_en') }}</label>
        <textarea name="description_en" rows="4" dir="ltr"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-blue-400">{{ old('description_en', $pkg?->description_en) }}</textarea>
    </div>
</div>

{{-- Media Upload --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
    <h3 class="font-bold text-gray-800">{{ __('travel.packages.media') }}</h3>

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
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('travel.packages.file_max_upload_title') }}</label>
        <input type="file" name="media[]" multiple accept="image/*,video/mp4,video/quicktime"
               class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100">
        <p class="mt-1 text-xs text-gray-400">{{ __('travel.packages.file_max_upload_subtitle') }}</p>
    </div>
</div>

<script>
(function () {
    const countrySelect = document.getElementById('travel-country-select');
    const citySelect    = document.getElementById('travel-city-select');
    const citiesUrl     = '{{ rtrim(url('/packages/cities-for-country'), '/') }}/';
    const preselectedCity = '{{ old('destination_travel_city_id', $pkg?->destination_travel_city_id ?? '') }}';

    function populateCities(cities, selectedId) {
        citySelect.innerHTML = '<option value="">— اختر المدينة (اختياري) —</option>';
        cities.forEach(function (c) {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name_en + (c.name_ar ? ' / ' + c.name_ar : '');
            if (c.id === selectedId) opt.selected = true;
            citySelect.appendChild(opt);
        });
        citySelect.disabled = cities.length === 0;
    }

    function loadCities(countryId, selectedId) {
        if (!countryId) {
            citySelect.innerHTML = '<option value="">— اختر الدولة أولاً —</option>';
            citySelect.disabled = true;
            return;
        }
        fetch(citiesUrl + countryId)
            .then(function (r) { return r.json(); })
            .then(function (cities) { populateCities(cities, selectedId || ''); })
            .catch(function () { citySelect.disabled = true; });
    }

    countrySelect.addEventListener('change', function () {
        loadCities(this.value, '');
    });

    // On edit: pre-populate cities for the already-selected country
    const initialCountry = countrySelect.value;
    if (initialCountry) {
        loadCities(initialCountry, preselectedCity);
    }
})();
</script>
