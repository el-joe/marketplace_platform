@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/app-contexts.js'])
@endpush

@section('title', $context->name_en)

@section('content')

    <div class="mb-6">
        <a href="{{ route('admin.app-contexts.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
            {{ __('admin.app_contexts.back_to_contexts') }}
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ $context->name_en }}</h1>
    </div>

    <div x-data="{ tab: 'general' }" data-context-key="{{ $context->key }}">

        {{-- Tabs --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex gap-6">
                <button type="button" @click="tab = 'general'"
                        :class="tab === 'general' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                    {{ __('admin.app_contexts.general_settings') }}
                </button>
                <button type="button" @click="tab = 'countries'"
                        :class="tab === 'countries' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                    {{ __('admin.app_contexts.country_assignment') }}
                </button>
                <button type="button" @click="tab = 'nav'"
                        :class="tab === 'nav' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm">
                    {{ __('admin.app_contexts.bottom_navigation') }}
                </button>
            </nav>
        </div>

        {{-- TAB 1: General Settings --}}
        <div x-show="tab === 'general'" class="card max-w-2xl p-6">
            <form id="general-settings-form" method="POST" action="{{ route('admin.app-contexts.update', $context) }}"
                  enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PUT')

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">{{ __('admin.app_contexts.name_english') }}</label>
                        <input type="text" name="name_en" value="{{ old('name_en', $context->name_en) }}" dir="ltr" class="input" required>
                    </div>
                    <div>
                        <label class="label">{{ __('admin.app_contexts.name_arabic') }}</label>
                        <input type="text" name="name_ar" value="{{ old('name_ar', $context->name_ar) }}" dir="rtl" class="input font-arabic" required>
                    </div>
                </div>

                <div>
                    <label class="label">{{ __('admin.app_contexts.icon') }}</label>
                    @if ($context->icon_path)
                        <div class="mb-2">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($context->icon_path) }}"
                                 class="w-14 h-14 object-contain rounded-lg border border-gray-200 p-1">
                        </div>
                    @endif
                    <input type="file" name="icon" id="context-icon-filepond" accept="image/*">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">{{ __('admin.app_contexts.color') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color_hex" value="{{ old('color_hex', $context->color_hex ?: '#0F172A') }}"
                                   class="h-10 w-14 rounded border border-gray-300">
                        </div>
                    </div>
                    <div>
                        <label class="label">{{ __('admin.app_contexts.sort_order') }}</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $context->sort_order) }}" class="input">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           @checked(old('is_active', $context->is_active)) class="rounded border-gray-300 text-primary-600">
                    <label for="is_active" class="text-sm text-gray-700">{{ __('admin.app_contexts.active') }}</label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="btn btn-primary">{{ __('admin.app_contexts.save_changes') }}</button>
                </div>
            </form>
        </div>

        {{-- TAB 2: Country Assignment & Home Pages --}}
        <div x-show="tab === 'countries'" class="card p-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200">
                        <th class="py-2 pr-4">{{ __('admin.app_contexts.country') }}</th>
                        <th class="py-2 pr-4">{{ __('admin.app_contexts.active') }}</th>
                        <th class="py-2 pr-4">{{ __('admin.app_contexts.home_page') }}</th>
                        <th class="py-2 pr-4">{{ __('admin.app_contexts.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($countries as $country)
                        @php $assignment = $assignments->get($country->id) @endphp
                        <tr class="border-b border-gray-100 js-country-row" data-country-id="{{ $country->id }}">
                            <td class="py-2 pr-4">
                                {{ $country->flag_emoji ? $country->flag_emoji . ' ' : '' }}{{ $country->name_en }}
                            </td>
                            <td class="py-2 pr-4">
                                <input type="checkbox" class="js-country-active rounded border-gray-300 text-primary-600"
                                       @checked($assignment?->is_active)>
                            </td>
                            <td class="py-2 pr-4">
                                <select class="js-country-home-page input" data-select2-init style="min-width: 220px;">
                                    <option value="">{{ __('admin.app_contexts.no_home_page') }}</option>
                                    @foreach ($pages as $page)
                                        <option value="{{ $page->id }}" @selected($assignment?->home_page_id === $page->id)>
                                            {{ $page->name }} ({{ $page->page_type }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-2 pr-4">
                                <button type="button" class="btn btn-xs btn-primary js-save-country-assignment">
                                    {{ __('admin.app_contexts.save') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TAB 3: Bottom Navigation --}}
        <div x-show="tab === 'nav'" class="space-y-6">

            <div class="card p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('admin.app_contexts.default_bottom_nav') }}</h3>
                <div class="flex gap-3 js-nav-slots" data-country-id="">
                    @for ($position = 1; $position <= 5; $position++)
                        @php $item = optional($navItems->get('default'))->firstWhere('position', $position) @endphp
                        @include('admin.app-contexts.partials.nav-slot', ['item' => $item, 'position' => $position])
                    @endfor
                </div>
            </div>

            @foreach ($navItems->except('default') as $countryId => $items)
                @php $country = $countries->firstWhere('id', $countryId) @endphp
                <div class="card p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">{{ optional($country)->name_en }} {{ __('admin.app_contexts.country') }}</h3>
                    <div class="flex gap-3 js-nav-slots" data-country-id="{{ $countryId }}">
                        @for ($position = 1; $position <= 5; $position++)
                            @php $item = $items->firstWhere('position', $position) @endphp
                            @include('admin.app-contexts.partials.nav-slot', ['item' => $item, 'position' => $position])
                        @endfor
                    </div>
                </div>
            @endforeach

            <button type="button" class="btn btn-secondary js-add-country-override">
                {{ __('admin.app_contexts.add_country_override') }}
            </button>
        </div>
    </div>

    @include('admin.app-contexts.partials.nav-item-modal')
    @include('admin.app-contexts.partials.country-override-modal')

    <script>
        window.APP_CONTEXT = {
            key: @json($context->key),
            saveCountryUrl: @json(route('admin.app-contexts.countries.save', $context)),
            saveNavUrl: @json(route('admin.app-contexts.nav.save', $context)),
            updateNavUrlBase: @json(route('admin.app-contexts.nav.save', $context)),
            countries: @json($countries->map(fn ($c) => ['id' => $c->id, 'name_en' => $c->name_en])->values()),
        };
    </script>

@endsection
