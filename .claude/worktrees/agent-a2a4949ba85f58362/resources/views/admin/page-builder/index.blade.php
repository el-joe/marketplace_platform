@extends('layouts.admin')

@section('title', __('admin.page_builder_section.title'))
@section('page-title', __('admin.page_builder_section.title'))

@push('styles')
    <style>
        /* The page-builder takes over the main canvas — kill default padding */
        #page-content { padding: 0 !important; }

        .pb-shell {
            display: grid;
            grid-template-columns: 240px 1fr 340px;
            height: calc(100vh - 60px);
            overflow: hidden;
            background: #f3f4f6;
        }
        .pb-panel { background: #ffffff; overflow-y: auto; }
        .pb-canvas { background: #f8fafc; overflow-y: auto; }

        .pb-group-title {
            text-transform: uppercase; letter-spacing: 0.05em;
            font-size: 11px; font-weight: 600; color: #6b7280;
            padding: 12px 16px 6px;
        }
        .palette-btn {
            display: flex; align-items: center; gap: 10px;
            width: 100%; padding: 10px 16px;
            font-size: 13px; color: #374151; text-align: start;
            border: none; background: transparent; cursor: pointer;
        }
        .palette-btn:hover { background: #f3f4f6; color: #111827; }
        .palette-btn svg { width: 18px; height: 18px; flex: 0 0 18px; color: #6b7280; }

        .block-canvas { padding: 20px; min-height: 100%; }
        .block-card {
            position: relative;
            background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px;
            padding: 14px 16px; margin-bottom: 10px;
            display: flex; align-items: center; gap: 12px;
            cursor: pointer;
            transition: box-shadow 120ms ease, border-color 120ms ease;
        }
        .block-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.06); border-color: #d1d5db; }
        .block-card.is-selected { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.2); }
        .block-card .drag-handle { color: #9ca3af; cursor: grab; padding: 4px; }
        .block-card .drag-handle:hover { color: #6b7280; }
        .block-card .block-icon { color: #6366f1; }
        .block-card .block-actions { opacity: 0; transition: opacity 120ms ease; }
        .block-card:hover .block-actions { opacity: 1; }
        .block-card.sortable-ghost { opacity: 0.4; background: #eef2ff; }

        #save-indicator { font-size: 12px; color: #6b7280; min-width: 80px; }
        #save-indicator.saving { color: #2563eb; }
        #save-indicator.saved { color: #059669; }
        #save-indicator.error { color: #dc2626; }
    </style>
@endpush

@section('content')

<div class="pb-shell">

    {{-- LEFT: Block palette --}}
    <aside class="pb-panel border-r border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">{{ __('admin.page_builder_section.blocks') }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ __('admin.page_builder_section.add_block') }}</p>
        </div>

        @foreach($blockTypes as $group => $items)
            <div class="pb-group-title">{{ ucfirst(str_replace('_', ' & ', $group)) }}</div>
            <div class="pb-group">
                @foreach($items as $type)
                    @php
                        $needsPerm = $type->requires_permission;
                        $allowed = ! $needsPerm || (auth('admin')->user()?->hasPermissionTo($needsPerm));
                    @endphp
                    @if($allowed)
                        <button type="button"
                                class="palette-btn"
                                data-block-type="{{ $type->code }}"
                                data-max-per-page="{{ $type->max_per_page ?? '' }}"
                                title="{{ $type->description ?? $type->label_en }}">
                            <x-heroicon :name="$type->icon ?? 'cube'" class="w-5 h-5" />
                            <span class="flex-1">{{ $type->label_en }}</span>
                        </button>
                    @endif
                @endforeach
            </div>
        @endforeach
    </aside>

    {{-- CENTER: Canvas --}}
    <main class="pb-canvas flex flex-col">

        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 bg-white">
            <label class="text-sm font-medium text-gray-700">{{ __('admin.page_builder_section.pages') }}:</label>
            <select id="page-select" class="w-72 rounded-lg border-gray-300 text-sm">
                <option value="">— {{ __('admin.page_builder_section.pages') }} —</option>
                @foreach($pages as $p)
                    <option value="{{ $p->id }}">
                        {{ $p->name }} ({{ $p->page_type }} · {{ optional($countries->firstWhere('id', $p->country_id))->site_code ?? '—' }})
                    </option>
                @endforeach
            </select>

            <button type="button" id="create-page-btn"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-primary-600 hover:text-primary-700">
                <x-heroicon name="plus" class="w-4 h-4" /> {{ __('admin.page_builder_section.add_page') }}
            </button>

            <span id="save-indicator"></span>

            <div class="flex-1"></div>

            <button type="button" id="version-history-btn"
                    class="hidden inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
                <x-heroicon name="clock" class="w-4 h-4" /> {{ __('admin.page_builder_section.versions') }}
            </button>

            <a href="#" id="preview-btn" target="_blank"
               class="hidden inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
                <x-heroicon name="eye" class="w-4 h-4" /> {{ __('admin.page_builder_section.preview') }}
            </a>

            <button type="button" id="publish-btn"
                    class="hidden inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
                <x-heroicon name="check-circle" class="w-4 h-4" /> {{ __('admin.page_builder_section.publish') }}
            </button>
        </div>

        <div class="block-canvas flex-1">
            <div id="canvas-empty" class="text-center text-gray-400 py-20">
                <x-heroicon name="squares-2x2" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p class="text-sm">{{ __('admin.page_builder_section.no_pages') }}</p>
            </div>
            <div id="block-canvas" class="hidden"></div>
        </div>
    </main>

    {{-- RIGHT: Config panel --}}
    <aside class="pb-panel border-l border-gray-200">
        <div id="config-panel" class="hidden flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 id="config-title" class="text-sm font-semibold text-gray-900">{{ __('admin.page_builder_section.edit_block') }}</h3>
                    <p id="config-save-status" class="text-xs text-gray-500 mt-0.5"></p>
                </div>
                <button type="button" id="close-config-btn"
                        class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100"
                        aria-label="{{ __('common.cancel') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div id="config-form-body" class="flex-1 overflow-y-auto p-4 space-y-4"></div>
        </div>
        <div id="config-empty" class="flex items-center justify-center h-full text-center text-gray-400 px-6">
            <div>
                <x-heroicon name="adjustments-horizontal" class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                <p class="text-sm">{{ __('admin.page_builder_section.edit_block') }}</p>
            </div>
        </div>
    </aside>
</div>

@include('admin.page-builder.partials.create-page-modal')
@include('admin.page-builder.partials.slide-modal')
@include('admin.page-builder.partials.version-drawer')

@endsection

@push('scripts')
    @vite([
        'resources/js/components/flatpickr.js',
        'resources/js/components/file-upload.js',
        'resources/js/admin/page-builder.js',
    ])
@endpush
