{{--
    Shared Banner form partial.
    @include('admin.banners._form', ['mode' => 'create'])
    @include('admin.banners._form', ['mode' => 'edit', 'banner' => $banner])

    Parent view must wrap in <form> with proper action / @csrf / @method.
    Requires: $countries, $placements (BannerPlacementDefinition collection)
    For edit: $banner, $desktopImage (File|null), $mobileImage (File|null)
--}}
@php
    $banner      = $banner ?? null;
    $isEdit      = $banner !== null;
    $desktopImage = $desktopImage ?? null;
    $mobileImage  = $mobileImage ?? null;

    $val = function (string $field, $default = '') use ($isEdit, $banner) {
        return old($field, $isEdit ? ($banner->{$field} ?? $default) : $default);
    };

    $placementsJson = $placements->keyBy('code')->map(fn ($p) => [
        'name'          => $p->name,
        'width_px'      => $p->width_px,
        'height_px'     => $p->height_px,
        'max_file_size_kb' => $p->max_file_size_kb,
        'allowed_formats'  => $p->allowed_formats,
        'device_restriction' => $p->device_restriction,
        'base_rate_weekly_cents' => $p->base_rate_weekly_cents,
    ])->toJson();

    $currentStatus       = old('status',       $isEdit ? $banner->status       : 'active');
    $currentDeviceTarget = old('device_target', $isEdit ? $banner->device_target : 'all');
    $currentLinkType     = old('link_type',     $isEdit ? $banner->link_type    : '');
    $currentPlacement    = old('placement_code',$isEdit ? $banner->placement_code : '');
@endphp

<div
    id="banner-form-root"
    x-data="{
        deviceTarget: '{{ $currentDeviceTarget }}',
        linkType:     '{{ $currentLinkType }}',
        placement:    '{{ $currentPlacement }}',
        placements:   {{ $placementsJson }},
        get currentPlacement() {
            return this.placements[this.placement] || null;
        },
        get dimensionHint() {
            const p = this.currentPlacement;
            if (!p) return '';
            const rate = (p.base_rate_weekly_cents / 100).toLocaleString('en-US', { style: 'currency', currency: 'USD' });
            return `${p.width_px} × ${p.height_px}px · max ${p.max_file_size_kb}KB · ${rate}/week`;
        },
        get desktopAspect() {
            const p = this.currentPlacement;
            if (!p || !p.width_px || !p.height_px) return '16/9';
            return p.width_px + '/' + p.height_px;
        },
        get showMobileUpload() {
            return this.deviceTarget === 'all' || this.deviceTarget === 'mobile' || this.deviceTarget === 'app';
        },
        get showLinkRef() {
            return this.linkType && this.linkType !== 'url' && this.linkType !== 'none' && this.linkType !== '';
        },
    }"
    class="space-y-6">

    <input type="hidden" name="_form_mode" value="{{ $isEdit ? 'edit' : 'create' }}">

    {{-- ─── Page header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $isEdit ? 'Edit Banner: ' . e($banner->name) : 'New Banner' }}
            </h1>
            @if($isEdit)
                <p class="text-sm text-gray-500 mt-1">
                    Last updated: {{ $banner->updated_at ? $banner->updated_at->diffForHumans() : 'never' }}
                </p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" id="save-btn" class="btn btn-primary">
                {{ $isEdit ? 'Save Changes' : 'Create Banner' }}
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ═══════════════════════════ LEFT COLUMN ═══════════════════════════ --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Banner Content card ─────────────────────────────────────────── --}}
            <x-card title="Banner Content">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Name --}}
                    <div class="sm:col-span-2">
                        <x-form-input
                            name="name"
                            label="Internal Name *"
                            :value="$val('name')"
                            placeholder="e.g. Ramadan Sale – Homepage Hero 2025"
                            required />
                    </div>

                    {{-- Placement --}}
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Placement <span class="text-red-500">*</span>
                        </label>
                        <select name="placement_code" x-model="placement" class="form-input w-full" required>
                            <option value="">— Select a placement —</option>
                            @foreach($placements as $p)
                                <option value="{{ $p->code }}" @selected($val('placement_code') === $p->code)>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1" x-text="dimensionHint"></p>
                    </div>

                </div>
            </x-card>

            {{-- Image Upload card ───────────────────────────────────────────── --}}
            <x-card title="Creative Images">

                {{-- Desktop image --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Desktop Image
                        <span class="text-xs text-gray-400 font-normal"
                            x-text="currentPlacement ? '(' + currentPlacement.width_px + '×' + currentPlacement.height_px + 'px)' : ''">
                        </span>
                    </label>

                    <div
                        id="desktop-preview-wrap"
                        class="relative rounded-lg overflow-hidden bg-gray-100 border border-dashed border-gray-300 mb-3 flex items-center justify-center"
                        style="min-height:120px"
                        :style="currentPlacement ? `aspect-ratio: ${desktopAspect}; max-height:240px;` : ''">
                        @if($isEdit && $desktopImage)
                            <img
                                id="desktop-preview-img"
                                src="{{ $desktopImage->full_path }}"
                                alt="Desktop"
                                class="max-w-full max-h-full object-contain" />
                            <button
                                type="button"
                                class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs js-remove-img-btn"
                                data-slot="desktop"
                                data-file-id="{{ $desktopImage->id }}"
                                data-delete-url="{{ route('admin.banners.delete-image') }}"
                                title="Remove">✕</button>
                        @else
                            <img id="desktop-preview-img" src="" alt="" class="max-w-full max-h-full object-contain hidden" />
                            <span id="desktop-upload-placeholder" class="text-sm text-gray-400">No image yet</span>
                        @endif
                    </div>

                    <input
                        type="file"
                        name="desktop_image"
                        id="desktop-image-input"
                        accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer" />
                </div>

                {{-- Mobile image --}}
                <div x-show="showMobileUpload" x-transition>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Mobile Image
                        <span class="text-xs text-gray-400 font-normal">(Optional — shown on mobile devices)</span>
                    </label>

                    <div
                        id="mobile-preview-wrap"
                        class="relative rounded-lg overflow-hidden bg-gray-100 border border-dashed border-gray-300 mb-3 flex items-center justify-center"
                        style="min-height:120px; max-width:200px">
                        @if($isEdit && $mobileImage)
                            <img
                                id="mobile-preview-img"
                                src="{{ $mobileImage->full_path }}"
                                alt="Mobile"
                                class="max-w-full max-h-full object-contain" />
                            <button
                                type="button"
                                class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs js-remove-img-btn"
                                data-slot="mobile"
                                data-file-id="{{ $mobileImage->id }}"
                                data-delete-url="{{ route('admin.banners.delete-image') }}"
                                title="Remove">✕</button>
                        @else
                            <img id="mobile-preview-img" src="" alt="" class="max-w-full max-h-full object-contain hidden" />
                            <span id="mobile-upload-placeholder" class="text-sm text-gray-400">No image yet</span>
                        @endif
                    </div>

                    <input
                        type="file"
                        name="mobile_image"
                        id="mobile-image-input"
                        accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer" />
                </div>

            </x-card>

            {{-- Text Content card ───────────────────────────────────────────── --}}
            <x-card title="Text Content" subtitle="All fields are optional">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-input name="title_en"    label="Title (English)"    :value="$val('title_en')" />
                    <x-form-input name="title_ar"    label="Title (Arabic)"     :value="$val('title_ar')" dir="rtl" />
                    <x-form-input name="subtitle_en" label="Subtitle (English)" :value="$val('subtitle_en')" />
                    <x-form-input name="subtitle_ar" label="Subtitle (Arabic)"  :value="$val('subtitle_ar')" dir="rtl" />
                    <x-form-input name="cta_label_en" label="CTA Label (English)" :value="$val('cta_label_en')" placeholder="e.g. Shop Now" />
                    <x-form-input name="cta_label_ar" label="CTA Label (Arabic)"  :value="$val('cta_label_ar')" placeholder="تسوق الآن" dir="rtl" />
                </div>
            </x-card>

            {{-- Link card ───────────────────────────────────────────────────── --}}
            <x-card title="Link & Target">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Link Type</label>
                        <select name="link_type" x-model="linkType" class="form-input w-full">
                            <option value="">— None —</option>
                            <option value="url" @selected($val('link_type') === 'url')>External URL</option>
                            <option value="product" @selected($val('link_type') === 'product')>Product</option>
                            <option value="category" @selected($val('link_type') === 'category')>Category</option>
                            <option value="brand" @selected($val('link_type') === 'brand')>Brand</option>
                            <option value="flash_sale" @selected($val('link_type') === 'flash_sale')>Flash Sale</option>
                            <option value="page" @selected($val('link_type') === 'page')>Page</option>
                            <option value="none" @selected($val('link_type') === 'none')>No link</option>
                        </select>
                    </div>

                    <div>
                        <x-form-input
                            name="cta_url"
                            label="URL"
                            :value="$val('cta_url')"
                            placeholder="https://…" />
                    </div>

                    <div x-show="showLinkRef" x-transition class="sm:col-span-2">
                        <x-form-input
                            name="link_reference_id"
                            label="Reference ID"
                            :value="$val('link_reference_id')"
                            placeholder="UUID of product / category / brand / flash sale / page" />
                    </div>

                </div>
            </x-card>

        </div>
        {{-- ═══════════════════════════ RIGHT SIDEBAR ═══════════════════════════ --}}
        <div class="space-y-6">

            {{-- Settings card ───────────────────────────────────────────────── --}}
            <x-card title="Settings">
                <div class="space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select name="country_id" class="form-input w-full">
                            <option value="">— All Countries —</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->id }}" @selected($val('country_id') === $c->id)>
                                    {{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Device Target</label>
                        <select name="device_target" x-model="deviceTarget" class="form-input w-full">
                            <option value="all"     @selected($val('device_target', 'all') === 'all')>All Devices</option>
                            <option value="desktop" @selected($val('device_target', 'all') === 'desktop')>Desktop Only</option>
                            <option value="mobile"  @selected($val('device_target', 'all') === 'mobile')>Mobile Only</option>
                            <option value="app"     @selected($val('device_target', 'all') === 'app')>App Only</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Audience</label>
                        <select name="audience" class="form-input w-full">
                            <option value="all"       @selected($val('audience', 'all') === 'all')>Everyone</option>
                            <option value="guest"     @selected($val('audience', 'all') === 'guest')>Guests Only</option>
                            <option value="logged_in" @selected($val('audience', 'all') === 'logged_in')>Logged In</option>
                            <option value="vip"       @selected($val('audience', 'all') === 'vip')>VIP Members</option>
                            <option value="new_user"  @selected($val('audience', 'all') === 'new_user')>New Users</option>
                        </select>
                    </div>

                    <div>
                        <x-form-input
                            name="priority"
                            label="Priority"
                            type="number"
                            :value="$val('priority', 0)"
                            min="0"
                            placeholder="0 = lowest" />
                        <p class="text-xs text-gray-400 mt-1">Higher number = shown first when multiple banners share a slot.</p>
                    </div>

                </div>
            </x-card>

            {{-- Schedule card ───────────────────────────────────────────────── --}}
            <x-card title="Schedule">
                <div class="space-y-4">

                    <div>
                        <x-form-input
                            name="starts_at"
                            label="Starts At *"
                            type="datetime-local"
                            :value="$val('starts_at', $isEdit ? optional($banner->starts_at)->format('Y-m-d\TH:i') : '')"
                            required />
                    </div>

                    <div>
                        <x-form-input
                            name="ends_at"
                            label="Ends At *"
                            type="datetime-local"
                            :value="$val('ends_at', $isEdit ? optional($banner->ends_at)->format('Y-m-d\TH:i') : '')"
                            required />
                        <p class="text-xs text-red-500 mt-1 hidden" id="date-error">End date must be after start date.</p>
                    </div>

                    <div class="rounded bg-gray-50 px-3 py-2 text-xs text-gray-600" id="duration-preview">
                        —
                    </div>

                </div>
            </x-card>

            {{-- Status card ─────────────────────────────────────────────────── --}}
            <x-card title="Status">
                <div class="space-y-2">
                    @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'scheduled' => 'Scheduled'] as $statusVal => $statusLabel)
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input
                                type="radio"
                                name="status"
                                value="{{ $statusVal }}"
                                class="mt-0.5"
                                @checked($currentStatus === $statusVal)>
                            <span class="text-sm font-medium text-gray-700">{{ $statusLabel }}</span>
                        </label>
                    @endforeach
                    <p class="text-xs text-gray-400 mt-1">
                        If you choose Active but the start date is in the future, the banner will be set to Scheduled automatically.
                    </p>
                </div>
            </x-card>

            {{-- Stats card (edit only) ──────────────────────────────────────── --}}
            @if($isEdit)
                <x-card title="Performance Stats">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Impressions</dt>
                            <dd class="font-semibold text-gray-800">{{ number_format($banner->impressions_count) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Clicks</dt>
                            <dd class="font-semibold text-gray-800">{{ number_format($banner->clicks_count) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">CTR</dt>
                            <dd class="font-semibold text-gray-800">
                                @if($banner->impressions_count > 0)
                                    {{ round($banner->clicks_count / $banner->impressions_count * 100, 2) }}%
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Created</dt>
                            <dd class="text-gray-700">{{ $banner->created_at?->format('d M Y') }}</dd>
                        </div>
                    </dl>
                </x-card>
            @endif

        </div>
    </div>
</div>
