@extends('layouts.admin')

@push('styles')
    @vite([
        'resources/js/components/flatpickr.js',
        'resources/js/components/select2.js',
        'resources/js/components/file-upload.js',
        'resources/js/admin/page-builder.js',
    ])
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

    {{-- Pass data to JS --}}
    <script>
        window.PAGE_ID = '{{ $page->id }}';
        window.PAGE_URLS = {
            update: '{{ route('admin.page-builder.update', $page->id) }}',
            publish: '{{ route('admin.page-builder.publish', $page->id) }}',
            clone: '{{ route('admin.page-builder.clone', $page->id) }}',
            destroy: '{{ route('admin.page-builder.destroy', $page->id) }}',
            blocksReorder: '{{ route('admin.page-builder.blocks.reorder', $page->id) }}',
            blockStore: '{{ route('admin.page-builder.blocks.store', $page->id) }}',
            sectionStore: '{{ route('admin.page-builder.sections.store', $page->id) }}',
        };
        window.INITIAL_BLOCKS = @json($page->blocks->map(fn($b) => [
            'id' => $b->id,
            'block_type' => $b->block_type,
            'block_type_label' => $b->blockType?->label_en ?? $b->block_type,
            'position' => $b->position,
            'is_visible' => $b->is_visible,
            'section_id' => $b->section_id,
            'device_target' => $b->device_target,
            'config' => $b->config,
            'slides_count' => $b->slides->count(),
            'ad_images_count' => $b->adImageItems->count(),
            'products_count' => $b->blockProducts->count(),
        ]));
    </script>

    {{-- ═══ TOP BAR ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-5 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3 min-w-0">
            <h1 class="text-xl font-semibold text-gray-900 truncate">{{ $page->name }}</h1>
            <x-badge :color="$statusColors[$page->status] ?? 'gray'" class="flex-shrink-0">
                {{ ucfirst($page->status) }}
            </x-badge>
            <span class="text-xs text-gray-400 flex-shrink-0">v{{ $page->version }}</span>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            @if($page->status === 'draft' || $page->status === 'scheduled')
                <button type="button" id="btn-publish" class="btn btn-success btn-sm">
                    <x-heroicon name="globe-alt" class="w-4 h-4 mr-1.5" />
                    Publish
                </button>
            @elseif($page->status === 'published')
                <button type="button" id="btn-unpublish" class="btn btn-secondary btn-sm">
                    Unpublish
                </button>
            @endif

            <div x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" class="btn btn-ghost btn-sm">
                    <x-heroicon name="ellipsis-vertical" class="w-4 h-4" />
                </button>
                <div x-show="open" @click.outside="open = false" x-transition
                    class="absolute right-0 top-full mt-1 z-20 w-40 rounded-lg bg-white shadow-lg border border-gray-100 py-1">
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

    {{-- ═══ EDITOR LAYOUT ═══════════════════════════════════════════════════ --}}
    <div class="flex gap-5 items-start">

        {{-- ─── LEFT PANEL: Block Picker ──────────────────────────────────── --}}
        <div class="w-60 flex-shrink-0 space-y-4 sticky top-20">

            {{-- Page info --}}
            <x-card>
                <div class="text-xs space-y-1.5 text-gray-600">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Type</span>
                        <span class="capitalize font-medium">{{ $page->page_type }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Country</span>
                        <span>{{ $page->country?->name_en ?? 'All' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Slug</span>
                        <span class="font-mono truncate max-w-[100px]" title="{{ $page->slug }}">{{ $page->slug }}</span>
                    </div>
                    @if($page->publishedAt)
                        <div class="flex justify-between">
                            <span class="text-gray-400">Published</span>
                            <span>{{ $page->published_at->format('M j, Y') }}</span>
                        </div>
                    @endif
                </div>
            </x-card>

            {{-- Add Section --}}
            <button type="button" id="btn-add-section"
                class="w-full text-sm text-center text-primary-600 hover:text-primary-800 py-2 rounded-lg border border-dashed border-primary-300 hover:border-primary-400 transition-colors">
                + Add Section
            </button>

            {{-- Block type picker --}}
            <x-card title="Add Block">
                <div class="space-y-4">
                    @foreach($blockTypes as $group => $types)
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400 mb-1.5">{{ $group }}</p>
                            <div class="space-y-1">
                                @foreach($types as $type)
                                    <button type="button"
                                        class="add-block-btn w-full text-left text-xs px-2.5 py-1.5 rounded-md hover:bg-primary-50 hover:text-primary-700 transition-colors flex items-center gap-2"
                                        data-block-type="{{ $type->code }}" title="{{ $type->description }}">
                                        @if($type->icon)
                                            <span class="text-base">{{ $type->icon }}</span>
                                        @endif
                                        {{ $type->label_en }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

        </div>{{-- /left panel --}}

        {{-- ─── CANVAS ─────────────────────────────────────────────────────── --}}
        <div class="flex-1 min-w-0">

            {{-- Section headers + block cards --}}
            @if($page->sections->isEmpty() && $page->blocks->isEmpty())
                <div id="empty-canvas"
                    class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-24 text-center">
                    <div class="text-4xl mb-3">🏗️</div>
                    <p class="text-gray-500 font-medium">Canvas is empty</p>
                    <p class="text-sm text-gray-400 mt-1">Click a block type on the left to add it to this page.</p>
                </div>
            @endif

            {{-- Sections --}}
            @foreach($page->sections as $section)
                <div class="page-section mb-4" data-section-id="{{ $section->id }}">
                    <div class="flex items-center gap-2 mb-2 group">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider flex-1">
                            {{ $section->name }}
                        </span>
                        <button type="button" class="btn-section-edit hidden group-hover:inline-flex btn btn-ghost btn-xs"
                            data-section-id="{{ $section->id }}" data-section-name="{{ $section->name }}">Edit</button>
                        <button type="button" class="btn-section-delete hidden group-hover:inline-flex btn btn-danger btn-xs"
                            data-section-id="{{ $section->id }}">Delete</button>
                    </div>
                    <div class="section-blocks-list space-y-3 min-h-[60px] rounded-lg border-2 border-dashed border-gray-100 p-2"
                        data-section-id="{{ $section->id }}">
                        @foreach($page->blocks->where('section_id', $section->id) as $block)
                            @include('admin.page-builder.partials.block-card', ['block' => $block])
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Unsectioned blocks --}}
            <div id="canvas-blocks" class="space-y-3 min-h-[120px]">
                @foreach($page->blocks->whereNull('section_id') as $block)
                    @include('admin.page-builder.partials.block-card', ['block' => $block])
                @endforeach
            </div>

        </div>{{-- /canvas --}}

    </div>

    {{-- ═══ MODALS ══════════════════════════════════════════════════════════ --}}

    {{-- Block Config Modal --}}
    <x-modal id="block-config-modal" title="Configure Block" size="lg">
        <div id="block-config-body" class="min-h-[200px]">
            {{-- Loaded dynamically by JS based on block_type --}}
        </div>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="button" id="btn-save-block-config" class="btn btn-primary">Save Changes</button>
        </x-slot:footer>
    </x-modal>

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
                    onclick="document.querySelector('[name=action]').value='schedule'; document.getElementById('schedule-date-wrap').classList.remove('hidden');">
                    Schedule Instead
                </button>
            </div>
        </form>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="submit" form="publish-form" class="btn btn-success">Publish</button>
        </x-slot:footer>
    </x-modal>

    {{-- Add Section Modal --}}
    <x-modal id="add-section-modal" title="Add Section" size="sm">
        <form id="add-section-form" class="space-y-4">
            @csrf
            <input type="hidden" name="_section_id">
            <div>
                <label class="form-label">Section Name <span class="text-danger-500">*</span></label>
                <input type="text" name="name" class="form-input w-full" placeholder="e.g. Hero Area" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Padding Top (px)</label>
                    <input type="number" name="padding_top" class="form-input w-full" min="0" value="0">
                </div>
                <div>
                    <label class="form-label">Padding Bottom (px)</label>
                    <input type="number" name="padding_bottom" class="form-input w-full" min="0" value="0">
                </div>
            </div>
            <div>
                <label class="form-label">Background Color</label>
                <input type="color" name="background_color" class="form-input w-full h-9" value="#ffffff">
            </div>
        </form>
        <x-slot:footer>
            <button type="button" data-modal-close class="btn btn-ghost">Cancel</button>
            <button type="submit" form="add-section-form" class="btn btn-primary">Save Section</button>
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

    {{-- Block Revisions Modal --}}
    <x-modal id="revisions-modal" title="Block Revision History" size="md">
        <div id="revisions-body" class="text-sm text-gray-600">Loading…</div>
    </x-modal>

    {{-- Config templates (hidden, cloned by JS) --}}
    @include('admin.page-builder.partials.config-templates')

@endsection