@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/settings.js'])
@endpush

@section('title', 'System Settings')

@section('content')

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
        <p class="text-sm text-gray-500 mt-0.5">Manage platform-wide configuration by category.</p>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- ─── Alpine Tabs ──────────────────────────────────────────────────────── --}}
    <div x-data="{ activeTab: '{{ $settings->keys()->first() }}' }">

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex gap-0 overflow-x-auto">
                @foreach($settings->keys() as $category)
                    <button
                        @click="activeTab = '{{ $category }}'"
                        :class="activeTab === '{{ $category }}'
                            ? 'border-primary-600 text-primary-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="shrink-0 border-b-2 px-5 py-3 text-sm font-medium transition-colors whitespace-nowrap">
                        {{ ucfirst($category) }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab Panels --}}
        @foreach($settings as $category => $categorySettings)
            <div x-show="activeTab === '{{ $category }}'" x-cloak>

                <form class="js-settings-form"
                      data-save-url="{{ route('admin.settings.update-group', $category) }}"
                      novalidate>
                    @csrf

                    <x-card>
                        <div class="divide-y divide-gray-100">

                            @foreach($categorySettings as $setting)
                                @php
                                    $valueType    = $setting->getValueType();
                                    $typedValue   = $setting->getTypedValue();
                                    $inputName    = "settings[{$setting->key}]";
                                    $inputId      = "setting-{$setting->key}";
                                    $labelText    = ucwords(str_replace('_', ' ', $setting->key));
                                @endphp

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 py-5 first:pt-0 last:pb-0">

                                    {{-- Label + description --}}
                                    <div class="sm:col-span-1">
                                        <label for="{{ $inputId }}" class="block text-sm font-medium text-gray-800">
                                            {{ $labelText }}
                                            @if($setting->is_encrypted)
                                                <span class="ml-1 inline-flex items-center px-1 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">encrypted</span>
                                            @endif
                                        </label>
                                        @if($setting->description)
                                            <p class="mt-1 text-xs text-gray-500">{{ $setting->description }}</p>
                                        @endif
                                        <p class="mt-1 text-xs font-mono text-gray-400">{{ $setting->key }}</p>
                                    </div>

                                    {{-- Input --}}
                                    <div class="sm:col-span-2 flex items-start">

                                        @if($setting->is_encrypted)
                                            {{-- Encrypted: masked display with show-to-update toggle --}}
                                            <div class="w-full" x-data="{ reveal: false }">
                                                <div x-show="!reveal" class="flex items-center gap-3">
                                                    <span class="text-sm text-gray-400 tracking-widest">●●●●●●●●</span>
                                                    <button type="button"
                                                            @click="reveal = true"
                                                            class="text-xs text-primary-600 hover:underline">Update</button>
                                                </div>
                                                <div x-show="reveal" x-cloak class="flex items-center gap-2">
                                                    <input type="password"
                                                           id="{{ $inputId }}"
                                                           name="{{ $inputName }}"
                                                           placeholder="Enter new value…"
                                                           autocomplete="off"
                                                           class="form-input w-full text-sm">
                                                    <button type="button"
                                                            @click="reveal = false"
                                                            class="text-xs text-gray-500 hover:text-gray-700 shrink-0">Cancel</button>
                                                </div>
                                            </div>

                                        @elseif($valueType === 'bool')
                                            {{-- Boolean toggle --}}
                                            <x-form-toggle
                                                :id="$inputId"
                                                :name="$inputName"
                                                :checked="(bool) $typedValue"
                                                trueValue="1"
                                                falseValue="0" />

                                        @elseif($valueType === 'number')
                                            <input type="number"
                                                   id="{{ $inputId }}"
                                                   name="{{ $inputName }}"
                                                   value="{{ $typedValue }}"
                                                   step="{{ is_float($typedValue) ? '0.01' : '1' }}"
                                                   class="form-input w-full sm:max-w-[16rem] text-sm">

                                        @elseif($valueType === 'array')
                                            <textarea id="{{ $inputId }}"
                                                      name="{{ $inputName }}"
                                                      rows="3"
                                                      class="form-input w-full font-mono text-xs"
                                                      placeholder="JSON array or object…">{{ json_encode($typedValue, JSON_PRETTY_PRINT) }}</textarea>

                                        @else
                                            <input type="text"
                                                   id="{{ $inputId }}"
                                                   name="{{ $inputName }}"
                                                   value="{{ $typedValue }}"
                                                   class="form-input w-full sm:max-w-sm text-sm">
                                        @endif

                                    </div>
                                </div>
                            @endforeach

                        </div>

                        <div class="mt-6 pt-5 border-t border-gray-100 flex items-center justify-between gap-3">
                            <span class="text-xs text-gray-400">
                                {{ $categorySettings->count() }} setting{{ $categorySettings->count() !== 1 ? 's' : '' }} in <strong>{{ $category }}</strong>
                            </span>
                            <button type="submit" class="btn btn-primary btn-sm js-save-btn">
                                Save {{ ucfirst($category) }} Settings
                            </button>
                        </div>

                    </x-card>
                </form>

            </div>
        @endforeach

    </div>

@endsection
