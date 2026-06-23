@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/admin/settings.js'])
@endpush

@section('title', 'System Settings')

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">System Settings</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage platform-wide configuration by category.</p>
        </div>
        <button id="btn-clear-cache" type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
            <x-heroicon name="arrow-path" class="w-4 h-4" />
            Clear Cache
        </button>
    </div>

    {{-- ─── URL-based Tab Navigation ─────────────────────────────────────────── --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex gap-0 overflow-x-auto" aria-label="Settings categories">

            @php
                $tabIcons = [
                    'appearance' => 'adjustments-horizontal',
                    'customers' => 'users',
                    'general' => 'cog-6-tooth',
                    'integrations' => 'cube',
                    'notifications' => 'bell',
                    'orders' => 'shopping-cart',
                    'security' => 'shield-check',
                    'vendors' => 'building-storefront',
                ];
            @endphp

            @foreach($categories as $cat)
                @php $icon = $tabIcons[$cat] ?? 'cog-6-tooth'; @endphp
                <a href="{{ route('admin.settings.index', ['tab' => $cat]) }}" class="flex items-center gap-1.5 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors
                                        {{ $activeCategory === $cat
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    <x-heroicon :name="$icon" class="w-4 h-4" />
                    {{ ucfirst($cat) }}
                </a>
            @endforeach

        </nav>
    </div>

    {{-- ─── Active Category Form ──────────────────────────────────────────────── --}}
    <form id="settings-form" data-category="{{ $activeCategory }}" novalidate>
        @csrf

        @include('admin.settings.partials.' . $activeCategory)

        {{-- Sticky save bar --}}
        <div class="sticky bottom-0 z-10 mt-6 -mx-6 border-t border-gray-200 bg-white px-6 py-4 shadow-md">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500">Changes apply immediately after saving.</p>
                <button type="submit" id="btn-save-settings"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-blue-700 disabled:opacity-60">
                    <x-heroicon name="check-circle" class="w-4 h-4" />
                    Save {{ ucfirst($activeCategory) }} Settings
                </button>
            </div>
        </div>

    </form>

@endsection