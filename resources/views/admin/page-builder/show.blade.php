@extends('layouts.admin')

@push('styles')
    {{-- Tabler Icons for the builder UI --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.5.0/dist/tabler-icons.min.css">

    @vite([
        'resources/js/components/flatpickr.js',
        'resources/js/components/select2.js',
        'resources/js/components/file-upload.js',
        'resources/js/admin/page-builder.js',
    ])

    <style>
        /* ─── Page Builder workspace ─────────────────────────────────────── */
        .pb {
            display: grid;
            grid-template-columns: 240px 1fr;
            height: calc(100vh - 180px);
            min-height: 600px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .pb .sidebar {
            background: #f9fafb;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .pb .sb-head {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            letter-spacing: .04em;
        }

        .pb .sb-scroll {
            overflow-y: auto;
            flex: 1;
            padding: 8px;
        }

        .pb .sb-group {
            margin-bottom: 6px;
        }

        .pb .sb-glabel {
            font-size: 10px;
            font-weight: 600;
            color: #9ca3af;
            letter-spacing: .06em;
            padding: 6px 6px 4px;
            text-transform: uppercase;
        }

        .pb .block-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 6px;
            cursor: grab;
            font-size: 12px;
            color: #374151;
            border: 1px solid transparent;
            transition: background .15s, border-color .15s;
            user-select: none;
        }

        .pb .block-pill:hover {
            background: #fff;
            border-color: #e5e7eb;
        }

        .pb .block-pill:active {
            cursor: grabbing;
        }

        .pb .block-pill i {
            font-size: 15px;
            color: #6b7280;
        }

        .pb .work-wrap {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
        }

        .pb .canvas-toolbar {
            padding: 8px 14px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            flex-wrap: wrap;
        }

        .pb .ct-label {
            font-size: 12px;
            color: #6b7280;
            flex: 1;
            min-width: 200px;
        }

        .pb .ct-label b {
            color: #111827;
            font-weight: 600;
        }

        .pb .ct-btn {
            font-size: 11px;
            padding: 5px 12px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
            cursor: pointer;
            font-weight: 500;
        }

        .pb .ct-btn:hover {
            background: #f9fafb;
        }

        .pb .ct-btn.primary {
            background: #4f46e5;
            color: #fff;
            border-color: #4f46e5;
        }

        .pb .ct-btn.primary:hover {
            background: #4338ca;
        }

        .pb .ct-btn.success {
            background: #059669;
            color: #fff;
            border-color: #059669;
        }

        .pb .ct-btn.success:hover {
            background: #047857;
        }

        .pb .canvas-row {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .pb .canvas {
            flex: 1;
            overflow-y: auto;
            padding: 14px;
            background: #f3f4f6;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pb .drop-zone {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 36px 24px;
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
            transition: background .15s, border-color .15s;
        }

        .pb .drop-zone.over {
            background: #eef2ff;
            border-color: #6366f1;
            color: #4338ca;
        }

        .pb .canvas-block {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: border-color .15s, box-shadow .15s;
        }

        .pb .canvas-block:hover {
            border-color: #9ca3af;
        }

        .pb .canvas-block.selected {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, .15);
        }

        .pb .canvas-block.dragging {
            opacity: .4;
        }

        .pb .cb-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .pb .cb-drag {
            cursor: grab;
            color: #9ca3af;
            font-size: 14px;
        }

        .pb .cb-type {
            font-size: 12px;
            font-weight: 500;
            color: #374151;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pb .cb-status {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            background: #f3f4f6;
            color: #6b7280;
        }

        .pb .cb-status.hidden {
            background: #fef3c7;
            color: #92400e;
        }

        .pb .cb-act {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #9ca3af;
            padding: 2px 4px;
            border-radius: 4px;
        }

        .pb .cb-act:hover {
            color: #374151;
            background: #f3f4f6;
        }

        .pb .cb-act.danger:hover {
            color: #dc2626;
            background: #fee2e2;
        }

        .pb .cb-preview {
            padding: 10px;
        }

        /* ─── Slide-out config panel ─────────────────────────────────────── */
        .pb .panel {
            width: 320px;
            border-left: 1px solid #e5e7eb;
            background: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: width .2s;
            flex-shrink: 0;
        }

        .pb .panel.closed {
            width: 0;
        }

        .pb .panel-head {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pb .panel-scroll {
            overflow-y: auto;
            flex: 1;
            padding: 14px;
        }

        .pb .panel-foot {
            padding: 10px 14px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .pb .field {
            margin-bottom: 14px;
        }

        .pb .field label {
            display: block;
            font-size: 11px;
            color: #4b5563;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .pb .field input,
        .pb .field select,
        .pb .field textarea {
            width: 100%;
            font-size: 12px;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
        }

        .pb .field textarea {
            resize: vertical;
            min-height: 60px;
        }

        .pb .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: #111827;
            margin-bottom: 10px;
        }

        .pb .tog {
            width: 30px;
            height: 16px;
            background: #d1d5db;
            border-radius: 8px;
            position: relative;
            cursor: pointer;
            transition: background .2s;
            flex-shrink: 0;
        }

        .pb .tog.on {
            background: #4f46e5;
        }

        .pb .tog::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
        }

        .pb .tog.on::after {
            transform: translateX(14px);
        }

        .pb .section-label {
            font-size: 10px;
            font-weight: 600;
            color: #9ca3af;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 8px;
            padding-top: 4px;
        }

        /* ─── Block previews ─────────────────────────────────────────────── */
        .prev-slider {
            background: linear-gradient(90deg, #1e3a8a, #92400e);
            border-radius: 6px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            font-size: 11px;
            color: #fff;
        }

        .prev-adgrid {
            display: grid;
            gap: 6px;
        }

        .prev-adgrid.col2 {
            grid-template-columns: 1fr 1fr;
        }

        .prev-adgrid.col3 {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .prev-adgrid.col4 {
            grid-template-columns: 1fr 1fr 1fr 1fr;
        }

        .prev-ad {
            background: #f3f4f6;
            border-radius: 6px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #9ca3af;
            border: 1px solid #e5e7eb;
        }

        .prev-products {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
        }

        .prev-product {
            background: #f9fafb;
            border-radius: 5px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .prev-product-img {
            background: #e5e7eb;
            height: 50px;
        }

        .prev-product-info {
            padding: 5px 6px;
            font-size: 10px;
            color: #6b7280;
        }

        .prev-flash {
            background: #fef2f2;
            border-radius: 6px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .prev-flash-badge {
            background: #dc2626;
            color: #fff;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 500;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .prev-flash-timer {
            font-size: 11px;
            color: #dc2626;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .prev-flash-products {
            display: flex;
            gap: 5px;
            flex: 1;
            overflow: hidden;
        }

        .prev-flash-item {
            background: #fff;
            border-radius: 5px;
            flex: 1;
            min-width: 0;
            height: 50px;
            border: 1px solid #fecaca;
        }

        .prev-banner {
            background: #ecfdf5;
            border-radius: 6px;
            height: 50px;
            display: flex;
            align-items: center;
            padding: 0 12px;
            gap: 10px;
            font-size: 11px;
            color: #065f46;
        }

        .prev-countdown {
            background: #f3f4f6;
            border-radius: 6px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 11px;
            color: #111827;
        }

        .prev-countdown span.box {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 4px 8px;
            font-weight: 600;
        }

        .prev-sponsored {
            background: #fffbeb;
            border-radius: 6px;
            height: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 12px;
            font-size: 11px;
            color: #92400e;
        }

        .prev-cats {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .prev-cat {
            background: #f3f4f6;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 10px;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .prev-text {
            padding: 4px 0;
            font-size: 11px;
            color: #6b7280;
            line-height: 1.5;
        }

        .prev-divider {
            border-top: 1px solid #e5e7eb;
            margin: 8px 0;
        }

        .prev-video {
            background: #f3f4f6;
            border-radius: 6px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 11px;
            color: #6b7280;
        }

        .prev-steps {
            display: flex;
            gap: 5px;
        }

        .prev-step {
            background: #f3f4f6;
            border-radius: 5px;
            flex: 1;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
            border: 1px solid #e5e7eb;
        }

        .prev-step b {
            color: #111827;
            font-size: 11px;
        }

        .prev-poll {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .prev-option {
            background: #f9fafb;
            border-radius: 5px;
            padding: 6px 10px;
            font-size: 10px;
            color: #4b5563;
        }

        .prev-option-bar {
            height: 3px;
            background: #e5e7eb;
            border-radius: 2px;
            margin-top: 4px;
            overflow: hidden;
        }

        .prev-option-fill {
            height: 100%;
            background: #6366f1;
        }
    </style>
@endpush

@section('title', 'Edit: ' . $page->name)

@section('content')
    @php
        $statusColors = [
            'draft' => 'gray',
            'published' => 'success',
            'scheduled' => 'primary',
            'archived' => 'warning',
        ];
    @endphp

    {{-- Pass server data to JS --}}
    <script>
        window.PAGE_ID = '{{ $page->id }}';
        window.PAGE_NAME = @json($page->name);
        window.PAGE_URL_LABEL = @json(($page->country?->code ? strtolower($page->country->code) : 'all') . ' · /' . trim($page->slug, '/'));
        window.PAGE_URLS = {
            update: '{{ route('admin.page-builder.update', $page->id) }}',
            publish: '{{ route('admin.page-builder.publish', $page->id) }}',
            clone: '{{ route('admin.page-builder.clone', $page->id) }}',
            destroy: '{{ route('admin.page-builder.destroy', $page->id) }}',
            blocksReorder: '{{ route('admin.page-builder.blocks.reorder', $page->id) }}',
            blockStore: '{{ route('admin.page-builder.blocks.store', $page->id) }}',
            sectionStore: '{{ route('admin.page-builder.sections.store', $page->id) }}',
            indexUrl: '{{ route('admin.page-builder.index') }}',
        };
        window.INITIAL_BLOCKS = @json($page->blocks->map(fn($b) => [
            'id' => $b->id,
            'block_type' => $b->block_type,
            'position' => $b->position,
            'is_visible' => (bool) $b->is_visible,
            'section_id' => $b->section_id,
            'device_target' => $b->device_target,
            'config' => $b->config ?? [],
            'visible_from' => $b->visible_from,
            'visible_until' => $b->visible_until,
            'cache_ttl_seconds' => $b->cache_ttl_seconds,
            'slides_count' => $b->slides->count(),
            'ad_images_count' => $b->adImageItems->count(),
            'products_count' => $b->blockProducts->count(),
        ])->values());
    </script>

    {{-- ─── TOP BAR ──────────────────────────────────────────────────────── --}}
    <div class="mb-4 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3 min-w-0">
            <a href="{{ route('admin.page-builder.index') }}" class="text-gray-400 hover:text-gray-700" title="Back">
                <x-heroicon name="arrow-left" class="w-5 h-5" />
            </a>
            <h1 class="text-xl font-semibold text-gray-900 truncate">{{ $page->name }}</h1>
            <x-badge :color="$statusColors[$page->status] ?? 'gray'">{{ ucfirst($page->status) }}</x-badge>
            <span class="text-xs text-gray-400">v{{ $page->version }}</span>
        </div>

        <div class="flex items-center gap-2">
            @if(in_array($page->status, ['draft', 'scheduled']))
                <button type="button" id="btn-publish" class="btn btn-success btn-sm">
                    <x-heroicon name="globe-alt" class="w-4 h-4 mr-1.5" /> Publish
                </button>
            @elseif($page->status === 'published')
                <button type="button" id="btn-unpublish" class="btn btn-secondary btn-sm">Unpublish</button>
            @endif

            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" class="btn btn-ghost btn-sm" aria-label="More actions">
                    <x-heroicon name="ellipsis-vertical" class="w-4 h-4" />
                </button>
                <div x-show="open" @click.outside="open = false" x-transition x-cloak
                    class="absolute right-0 top-full mt-1 z-20 w-44 rounded-lg bg-white shadow-lg border border-gray-100 py-1">
                    <button type="button" id="btn-schedule" class="dropdown-item">Schedule…</button>
                    <button type="button" id="btn-clone" class="dropdown-item">Clone Page</button>
                    <button type="button" id="btn-edit-meta" class="dropdown-item">Edit Meta…</button>
                    <div class="my-1 border-t border-gray-100"></div>
                    <button type="button" id="btn-archive" class="dropdown-item text-warning-700">Archive</button>
                    <button type="button" id="btn-delete" class="dropdown-item text-danger-700">Delete…</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── BUILDER ──────────────────────────────────────────────────────── --}}
    <div class="pb" id="pb">

        {{-- Left sidebar: block palette --}}
        <div class="sidebar">
            <div class="sb-head">Blocks</div>
            <div class="sb-scroll">
                @php
                    $groupLabels = [
                        'hero' => 'Hero',
                        'products' => 'Products',
                        'ads_banners' => 'Ads &amp; banners',
                        'discovery' => 'Discovery',
                        'engagement' => 'Engagement',
                    ];
                @endphp

                @foreach($groupLabels as $groupKey => $groupLabel)
                    @if(isset($blockTypes[$groupKey]))
                        <div class="sb-group">
                            <div class="sb-glabel">{!! $groupLabel !!}</div>
                            @foreach($blockTypes[$groupKey] as $type)
                                <div class="block-pill" draggable="true" data-block-type="{{ $type->code }}"
                                    title="{{ $type->description }}">
                                    <i class="ti {{ $type->icon ?: 'ti-square' }}" aria-hidden="true"></i>
                                    {{ $type->label_en }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Right: toolbar + canvas + config panel --}}
        <div class="work-wrap">
            <div class="canvas-toolbar">
                <span class="ct-label">
                    <b>{{ $page->name }}</b> &mdash;
                    <span class="text-gray-500">{{ $page->country?->name_en ?? 'All countries' }} ·
                        /{{ trim($page->slug, '/') }}</span>
                </span>
                <button type="button" class="ct-btn" id="btn-canvas-clear">Clear</button>
                <button type="button" class="ct-btn success" id="btn-canvas-save">Save layout</button>
            </div>

            <div class="canvas-row">
                <div class="canvas" id="canvas">
                    <div class="drop-zone" id="drop-hint">
                        <i class="ti ti-plus" style="font-size: 22px; display: block; margin-bottom: 6px;"
                            aria-hidden="true"></i>
                        Drag blocks here from the left to build the page
                    </div>
                </div>

                <div class="panel closed" id="config-panel">
                    <div class="panel-head">
                        <span id="panel-title">Configure block</span>
                        <button type="button" class="cb-act" id="btn-panel-close" aria-label="Close panel">
                            <i class="ti ti-x" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="panel-scroll" id="panel-body"></div>
                    <div class="panel-foot">
                        <button type="button" class="ct-btn" id="btn-panel-cancel">Cancel</button>
                        <button type="button" class="ct-btn primary" id="btn-panel-save">Save block</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ MODALS ═══════════════════════════════════════════════════════════ --}}

    {{-- Slide Edit Modal --}}
    <x-modal id="slide-edit-modal" title="Edit Slide" size="md">
        <form id="slide-edit-form" class="space-y-4">
            @csrf
            <input type="hidden" name="_slide_id">
            <input type="hidden" name="_block_id">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Desktop Image</label>
                    <input type="hidden" name="desktop_file_id">
                    <x-file-upload id="slide-desktop-upload" accept="image/*" :maxFiles="1"
                        onUpload="function(f){ document.querySelector('#slide-edit-form [name=desktop_file_id]').value = f.file_id; }" />
                </div>
                <div>
                    <label class="form-label">Mobile Image</label>
                    <input type="hidden" name="mobile_file_id">
                    <x-file-upload id="slide-mobile-upload" accept="image/*" :maxFiles="1"
                        onUpload="function(f){ document.querySelector('#slide-edit-form [name=mobile_file_id]').value = f.file_id; }" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Title (EN)</label>
                    <input type="text" name="title_en" class="form-input w-full" maxlength="255">
                </div>
                <div>
                    <label class="form-label">Title (AR)</label>
                    <input type="text" name="title_ar" class="form-input w-full text-right" dir="rtl" maxlength="255">
                </div>
                <div>
                    <label class="form-label">CTA Label (EN)</label>
                    <input type="text" name="cta_label_en" class="form-input w-full" maxlength="100">
                </div>
                <div>
                    <label class="form-label">CTA Label (AR)</label>
                    <input type="text" name="cta_label_ar" class="form-input w-full text-right" dir="rtl" maxlength="100">
                </div>
            </div>

            <div>
                <label class="form-label">CTA URL</label>
                <input type="url" name="cta_url" class="form-input w-full" maxlength="500">
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="form-label">Text Color</label>
                    <input type="color" name="text_color" class="form-input w-full h-9" value="#ffffff">
                </div>
                <div>
                    <label class="form-label">Text Position</label>
                    <select name="text_position" class="form-select w-full">
                        <option value="left">Left</option>
                        <option value="center">Center</option>
                        <option value="right">Right</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Overlay Opacity</label>
                    <input type="number" name="overlay_opacity" class="form-input w-full" min="0" max="1" step="0.05"
                        value="0.30">
                </div>
            </div>

            <x-form-toggle name="cta_open_new_tab" label="Open CTA in new tab" :value="false" />
            <x-form-toggle name="is_active" label="Slide is Active" :value="true" />
        </form>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="submit" form="slide-edit-form" class="btn btn-primary">Save Slide</button>
        </x-slot:footer>
    </x-modal>

    {{-- Ad Image Edit Modal --}}
    <x-modal id="ad-image-edit-modal" title="Edit Ad Image" size="md">
        <form id="ad-image-edit-form" class="space-y-4">
            @csrf
            <input type="hidden" name="_item_id">
            <input type="hidden" name="_block_id">

            <div>
                <label class="form-label">Image</label>
                <input type="hidden" name="file_id">
                <x-file-upload id="ad-image-upload" accept="image/*" :maxFiles="1"
                    onUpload="function(f){ document.querySelector('#ad-image-edit-form [name=file_id]').value = f.file_id; }" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Title (EN)</label>
                    <input type="text" name="title_en" class="form-input w-full" maxlength="255">
                </div>
                <div>
                    <label class="form-label">Title (AR)</label>
                    <input type="text" name="title_ar" class="form-input w-full text-right" dir="rtl" maxlength="255">
                </div>
                <div>
                    <label class="form-label">Alt Text (EN)</label>
                    <input type="text" name="alt_text_en" class="form-input w-full" maxlength="255">
                </div>
                <div>
                    <label class="form-label">Alt Text (AR)</label>
                    <input type="text" name="alt_text_ar" class="form-input w-full text-right" dir="rtl" maxlength="255">
                </div>
            </div>

            <div>
                <label class="form-label">Link URL</label>
                <input type="url" name="link_url" class="form-input w-full" maxlength="500">
            </div>

            <div>
                <label class="form-label">Aspect Ratio</label>
                <select name="aspect_ratio" class="form-select w-full">
                    <option value="4:3">4:3</option>
                    <option value="16:9">16:9</option>
                    <option value="1:1">1:1</option>
                    <option value="2:1">2:1</option>
                    <option value="3:4">3:4</option>
                </select>
            </div>

            <x-form-toggle name="link_open_new_tab" label="Open link in new tab" :value="false" />
            <x-form-toggle name="show_title_overlay" label="Show title overlay" :value="true" />
            <x-form-toggle name="is_active" label="Active" :value="true" />
        </form>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="submit" form="ad-image-edit-form" class="btn btn-primary">Save Image</button>
        </x-slot:footer>
    </x-modal>

    {{-- Publish / Schedule Modal --}}
    <x-modal id="publish-modal" title="Publish Page" size="sm">
        <form id="publish-form" class="space-y-4">
            @csrf
            <input type="hidden" name="action" value="publish">
            <div>
                <label class="form-label">Publish Reason (optional)</label>
                <input type="text" name="reason" class="form-input w-full" placeholder="e.g. Summer campaign launch">
            </div>
            <div id="schedule-date-wrap" class="hidden">
                <label class="form-label">Schedule Date &amp; Time</label>
                <input type="text" name="publish_at" class="form-input w-full flatpickr-datetime">
            </div>
            <div class="flex gap-2">
                <button type="button" class="flex-1 btn btn-ghost text-sm"
                    onclick="document.querySelector('#publish-form [name=action]').value='schedule'; document.getElementById('schedule-date-wrap').classList.remove('hidden');">
                    Schedule Instead
                </button>
            </div>
        </form>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="submit" form="publish-form" class="btn btn-success">Publish</button>
        </x-slot:footer>
    </x-modal>

    {{-- Edit Meta Modal --}}
    <x-modal id="edit-meta-modal" title="Edit Page Meta" size="sm">
        <form id="edit-meta-form" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Page Name</label>
                <input type="text" name="name" class="form-input w-full" value="{{ $page->name }}">
            </div>
            <div>
                <label class="form-label">SEO Title</label>
                <input type="text" name="seo_title" class="form-input w-full" value="{{ $page->seo_title }}"
                    maxlength="255">
            </div>
            <div>
                <label class="form-label">SEO Description</label>
                <textarea name="seo_description" rows="3" class="form-textarea w-full"
                    maxlength="500">{{ $page->seo_description }}</textarea>
            </div>
            <div>
                <label class="form-label">OG Image URL</label>
                <input type="url" name="og_image_url" class="form-input w-full" value="{{ $page->og_image_url }}"
                    maxlength="500">
            </div>
        </form>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="submit" form="edit-meta-form" class="btn btn-primary">Save</button>
        </x-slot:footer>
    </x-modal>

@endsection