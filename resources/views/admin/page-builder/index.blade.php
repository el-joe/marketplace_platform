@extends('layouts.admin')

@section('title', __('admin.page_builder.title'))
@section('page-title', __('admin.page_builder.title'))

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
            font-size: 13px; color: #374151; text-align: left;
            border: none; background: transparent; cursor: pointer;
        }
        .palette-btn:hover { background: #f3f4f6; color: #111827; }
        .palette-btn svg { width: 18px; height: 18px; flex: 0 0 18px; color: #6b7280; }

        .palette-info-btn {
            flex: 0 0 auto; display: flex; align-items: center; justify-content: center;
            width: 18px; height: 18px; border-radius: 999px; border: none;
            background: transparent; color: #9ca3af; cursor: pointer; padding: 0;
        }
        .palette-info-btn:hover { color: #6366f1; background: #eef2ff; }
        .palette-info-btn svg { width: 14px; height: 14px; }

        .palette-info-popover {
            position: fixed; z-index: 9999; width: 280px;
            background: #111827; color: #f3f4f6; font-size: 12px; line-height: 1.5;
            border-radius: 8px; padding: 10px 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.25);
            display: none;
        }
        .palette-info-popover.is-open { display: block; }
        .palette-info-popover::before {
            content: ''; position: absolute; left: -5px; top: 10px;
            width: 10px; height: 10px; background: #111827; transform: rotate(45deg);
        }

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
            <h2 class="text-sm font-semibold text-gray-900">{{ __('admin.page_builder.blocks') }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">{{ __('admin.page_builder.click_to_add') }}</p>
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
                        <div class="relative flex items-center">
                            <button type="button"
                                    class="palette-btn"
                                    data-block-type="{{ $type->code }}"
                                    data-max-per-page="{{ $type->max_per_page ?? '' }}"
                                    title="{{ $type->label_en }}">
                                <x-heroicon :name="$type->icon ?? 'cube'" class="w-5 h-5" />
                                <span class="flex-1">{{ $type->label_en }}</span>
                            </button>
                            @php
                                $description = app()->getLocale() === 'ar'
                                    ? ($type->description_ar ?: $type->description_en)
                                    : ($type->description_en ?: $type->description_ar);
                            @endphp
                            @if($description)
                                <button type="button"
                                        class="palette-info-btn"
                                        data-info-toggle
                                        aria-label="{{ __('admin.page_builder.block_info_aria') }}">
                                    <svg viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <div class="palette-info-popover" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" data-info-popover>{{ $description }}</div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        @endforeach

        @if($sliderPreviewBlocks->isNotEmpty())
            <div class="pb-group-title">{{ __('admin.page_builder.slider_preview') ?? 'Slider Preview' }}</div>
            <div class="px-3 py-2 space-y-3">
                <p class="text-xs text-gray-500">Live slides with resolved images — verify before publishing. Note: cart banners are managed separately under Banners, not here.</p>
                @foreach($sliderPreviewBlocks as $block)
                    <div class="border border-gray-200 rounded-lg p-2">
                        <div class="text-xs font-medium text-gray-700 mb-1">
                            {{ $block->page->name ?? 'Untitled page' }}
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @forelse($block->slides as $slide)
                                <div class="relative">
                                    @if($slide->desktopFile)
                                        <img src="{{ $slide->desktopFile->url }}" alt="" class="w-16 h-10 object-cover rounded border border-gray-200">
                                    @else
                                        <div class="w-16 h-10 rounded border border-dashed border-gray-300 flex items-center justify-center text-[10px] text-gray-400">No image</div>
                                    @endif
                                </div>
                            @empty
                                <span class="text-xs text-gray-400">No slides</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </aside>

    {{-- CENTER: Canvas --}}
    <main class="pb-canvas flex flex-col">

        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 bg-white">
            <label class="text-sm font-medium text-gray-700">{{ __('admin.page_builder.page') }}</label>
            <select id="page-select" class="w-72 rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('admin.page_builder.select_page_placeholder') }}</option>
                @foreach($pages as $p)
                    <option value="{{ $p->id }}">
                        {{ $p->name }} ({{ $p->page_type }} · {{ optional($countries->firstWhere('id', $p->country_id))->site_code ?? '—' }})
                    </option>
                @endforeach
            </select>

            <button type="button" id="create-page-btn"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-primary-600 hover:text-primary-700">
                <x-heroicon name="plus" class="w-4 h-4" /> {{ __('admin.page_builder.new_page') }}
            </button>

            <span id="save-indicator"></span>

            <div class="flex-1"></div>

            <button type="button" id="version-history-btn"
                    class="hidden inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
                <x-heroicon name="clock" class="w-4 h-4" /> {{ __('admin.page_builder.version_history') }}
            </button>

            <a href="#" id="preview-btn" target="_blank"
               class="hidden inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg">
                <x-heroicon name="eye" class="w-4 h-4" /> {{ __('admin.page_builder.preview') }}
            </a>

            <button type="button" id="publish-btn"
                    class="hidden inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
                <x-heroicon name="check-circle" class="w-4 h-4" /> {{ __('admin.page_builder.publish') }}
            </button>
        </div>

        <div id="home-page-banner" class="hidden mx-4 mt-3 px-4 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800"></div>

        <div class="block-canvas flex-1">
            <div id="canvas-empty" class="text-center text-gray-400 py-20">
                <x-heroicon name="squares-2x2" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                <p class="text-sm">{!! str_replace(':new_page', '<span class="font-medium">' . __('admin.page_builder.new_page') . '</span>', __('admin.page_builder.canvas_empty_hint')) !!}</p>
            </div>
            <div id="block-canvas" class="hidden"></div>
        </div>
    </main>

    {{-- RIGHT: Config panel --}}
    <aside class="pb-panel border-l border-gray-200">
        <div id="config-panel" class="hidden flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 id="config-title" class="text-sm font-semibold text-gray-900">{{ __('admin.page_builder.block_settings') }}</h3>
                    <p id="config-save-status" class="text-xs text-gray-500 mt-0.5"></p>
                </div>
                <button type="button" id="close-config-btn"
                        class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100"
                        aria-label="{{ __('common.close') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-4 border-b border-gray-200 flex items-center gap-4">
                <button type="button" data-config-tab="settings"
                        class="config-tab-btn py-2 text-xs font-medium border-b-2 border-primary-600 text-primary-700">
                    {{ __('admin.page_builder.settings') }}
                </button>
                <button type="button" data-config-tab="analytics"
                        class="config-tab-btn py-2 text-xs font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    {{ __('admin.page_builder.analytics') }}
                </button>
            </div>
            <div id="config-form-body" data-config-tab-panel="settings" class="flex-1 overflow-y-auto p-4 space-y-4"></div>
            @include('admin.page-builder.partials.block-analytics-tab')
        </div>
        <div id="config-empty" class="flex items-center justify-center h-full text-center text-gray-400 px-6">
            <div>
                <x-heroicon name="adjustments-horizontal" class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                <p class="text-sm">{{ __('admin.page_builder.select_block_hint') }}</p>
            </div>
        </div>
    </aside>
</div>

@include('admin.page-builder.partials.create-page-modal')
@include('admin.page-builder.partials.slide-modal')
@include('admin.page-builder.partials.version-drawer')

@endsection

@push('scripts')
    <script>
        window.TRANSLATIONS = window.TRANSLATIONS || {};
        Object.assign(window.TRANSLATIONS, {
            dragToReorder: @json(__('admin.page_builder_section.drag_to_reorder')),
            hidden: @json(__('admin.page_builder_section.hidden')),
            scheduled: @json(__('admin.page_builder_section.scheduled')),
            edit: @json(__('common.edit')),
            delete: @json(__('common.delete')),
            loading: @json(__('admin.page_builder_section.loading')),
            loadFailed: @json(__('admin.page_builder_section.load_failed')),
            couldNotLoadPage: @json(__('admin.page_builder_section.could_not_load_page')),
            noBlocksYet: @json(__('admin.page_builder_section.no_blocks_yet')),
            savingOrder: @json(__('admin.page_builder_section.saving_order')),
            orderSaved: @json(__('admin.page_builder_section.order_saved')),
            orderSaveFailed: @json(__('admin.page_builder_section.order_save_failed')),
            couldNotSaveOrder: @json(__('admin.page_builder_section.could_not_save_order')),
            selectOrCreatePageFirst: @json(__('admin.page_builder_section.select_or_create_page_first')),
            couldNotAddBlock: @json(__('admin.page_builder_section.could_not_add_block')),
            removeBlockTitle: @json(__('admin.page_builder_section.remove_block_title')),
            removeBlockMessage: @json(__('admin.page_builder_section.remove_block_message')),
            remove: @json(__('admin.page_builder_section.remove')),
            blockRemoved: @json(__('admin.page_builder_section.block_removed')),
            couldNotRemoveBlock: @json(__('admin.page_builder_section.could_not_remove_block')),
            blockSettings: @json(__('admin.page_builder_section.block_settings')),
            analyticsImpressions: @json(__('admin.page_builder_section.analytics_impressions')),
            analyticsClicks: @json(__('admin.page_builder_section.analytics_clicks')),
            analyticsNoData: @json(__('admin.page_builder_section.analytics_no_data')),
            analyticsLoadError: @json(__('admin.page_builder_section.analytics_load_error')),
            saving: @json(__('admin.page_builder_section.saving')),
            saveFailed: @json(__('admin.page_builder_section.save_failed')),
            deleteSlideTitle: @json(__('admin.page_builder_section.delete_slide_title')),
            deleteSlideMessage: @json(__('admin.page_builder_section.delete_slide_message')),
            slideDeleted: @json(__('admin.page_builder_section.slide_deleted')),
            couldNotDeleteSlide: @json(__('admin.page_builder_section.could_not_delete_slide')),
            imageUploaded: @json(__('admin.page_builder_section.image_uploaded')),
            uploadFailed: @json(__('admin.page_builder_section.upload_failed')),
            slideSaved: @json(__('admin.page_builder_section.slide_saved')),
            couldNotSaveSlide: @json(__('admin.page_builder_section.could_not_save_slide')),
            pageCreated: @json(__('admin.page_builder_section.page_created')),
            couldNotCreatePage: @json(__('admin.page_builder_section.could_not_create_page')),
            publishPageTitle: @json(__('admin.page_builder_section.publish_page_title')),
            publishPageMessage: @json(__('admin.page_builder_section.publish_page_message')),
            publish: @json(__('admin.page_builder_section.publish')),
            pagePublished: @json(__('admin.page_builder_section.page_published')),
            couldNotPublish: @json(__('admin.page_builder_section.could_not_publish')),
            restoreVersionTitle: @json(__('admin.page_builder_section.restore_version_title')),
            restoreVersionMessage: @json(__('admin.page_builder_section.restore_version_message')),
            restore: @json(__('admin.page_builder_section.restore')),
            versionRestored: @json(__('admin.page_builder_section.version_restored')),
            couldNotRestore: @json(__('admin.page_builder_section.could_not_restore')),
            inCountry: @json(__('admin.page_builder.in_country')),
            homePageBannerPrefix: @json(__('admin.page_builder.home_page_banner_prefix')),
            homePageBannerSuffix: @json(__('admin.page_builder.home_page_banner_suffix')),
        });
    </script>
    <script>
        // Block palette "what does this do?" info popovers.
        document.addEventListener('DOMContentLoaded', function () {
            document.addEventListener('click', function (e) {
                const toggleBtn = e.target.closest('[data-info-toggle]');

                document.querySelectorAll('.palette-info-popover.is-open').forEach(function (popover) {
                    if (!toggleBtn || popover !== toggleBtn.nextElementSibling) {
                        popover.classList.remove('is-open');
                    }
                });

                if (!toggleBtn) return;
                e.stopPropagation();
                const popover = toggleBtn.nextElementSibling;
                if (!popover || !popover.matches('[data-info-popover]')) return;
                popover.classList.toggle('is-open');
                if (popover.classList.contains('is-open')) {
                    positionInfoPopover(toggleBtn, popover);
                }
            });

            function positionInfoPopover(toggleBtn, popover) {
                const rect = toggleBtn.getBoundingClientRect();
                let left = rect.left;
                if (left + 280 > window.innerWidth - 8) {
                    left = window.innerWidth - 288;
                }
                popover.style.top = (rect.bottom + 4) + 'px';
                popover.style.left = Math.max(8, left) + 'px';
            }

            function closeAllInfoPopovers() {
                document.querySelectorAll('.palette-info-popover.is-open').forEach(function (popover) {
                    popover.classList.remove('is-open');
                });
            }

            document.querySelectorAll('.pb-panel, .pb-canvas').forEach(function (scrollable) {
                scrollable.addEventListener('scroll', closeAllInfoPopovers);
            });
            window.addEventListener('resize', closeAllInfoPopovers);
        });
    </script>
    @vite([
        'resources/js/components/flatpickr.js',
        'resources/js/components/file-upload.js',
        'resources/js/components/select2.js',
        'resources/js/admin/page-builder.js',
    ])
@endpush
