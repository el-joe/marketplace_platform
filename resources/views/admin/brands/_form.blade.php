{{--
    Shared Brand form partial.
    Include with: @include('admin.brands._form', ['mode' => 'create'])
                  @include('admin.brands._form', ['mode' => 'edit', 'brand' => $brand])

    Parent view must wrap this in a <form> tag with proper action / @csrf / @method.
--}}
@php
    $brand  = $brand ?? null;
    $isEdit = $brand !== null;

    $val = function (string $field, $default = '') use ($isEdit, $brand) {
        return old($field, $isEdit ? ($brand->{$field} ?? $default) : $default);
    };

    $bool = function (string $field, bool $default = false) use ($isEdit, $brand): bool {
        $raw = old($field, $isEdit ? ($brand->{$field} ?? $default) : $default);
        return (bool) $raw;
    };
@endphp

<div class="space-y-6">
    <input type="hidden" id="form-mode" name="_form_mode" value="{{ $isEdit ? 'edit' : 'create' }}">

    {{-- ─── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $isEdit ? 'Edit Brand: ' . e($brand->name_en) : 'New Brand' }}
            </h1>
        </div>
    </div>

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- LEFT: main fields                                               --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0 space-y-4">

            {{-- Names -------------------------------------------------------- --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Brand Names</h2>
                </div>
                <div class="px-5 py-5 space-y-4">

                    <div>
                        <label for="name_en" class="block text-xs font-medium text-gray-700 mb-1">
                            Name (English) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name_en"
                            name="name_en"
                            value="{{ $val('name_en') }}"
                            class="input w-full @error('name_en') border-red-400 @enderror"
                            placeholder="e.g. Apple"
                            required
                        />
                        @error('name_en') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="name_ar" class="block text-xs font-medium text-gray-700 mb-1">
                            Name (Arabic) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="name_ar"
                            name="name_ar"
                            value="{{ $val('name_ar') }}"
                            class="input w-full @error('name_ar') border-red-400 @enderror"
                            placeholder="أدخل الاسم بالعربية"
                            dir="rtl"
                            required
                        />
                        @error('name_ar') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="slug" class="block text-xs font-medium text-gray-700 mb-1">
                            Slug <span class="text-gray-400 font-normal">(auto-generated)</span>
                        </label>
                        <input
                            type="text"
                            id="slug"
                            name="slug"
                            value="{{ $val('slug') }}"
                            class="input w-full font-mono text-xs @error('slug') border-red-400 @enderror"
                            placeholder="e.g. apple"
                            data-slug-source="name_en"
                        />
                        @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="website_url" class="block text-xs font-medium text-gray-700 mb-1">Website URL</label>
                        <input
                            type="url"
                            id="website_url"
                            name="website_url"
                            value="{{ $val('website_url') }}"
                            class="input w-full @error('website_url') border-red-400 @enderror"
                            placeholder="https://example.com"
                        />
                        @error('website_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

            {{-- Description -------------------------------------------------- --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Description</h2>
                </div>
                <div class="px-5 py-5 space-y-4">

                    <div>
                        <label for="description_en" class="block text-xs font-medium text-gray-700 mb-1">Description (English)</label>
                        <textarea
                            id="description_en"
                            name="description_en"
                            rows="4"
                            class="input w-full @error('description_en') border-red-400 @enderror"
                            placeholder="Short description of the brand…"
                        >{{ $val('description_en') }}</textarea>
                        @error('description_en') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description_ar" class="block text-xs font-medium text-gray-700 mb-1">Description (Arabic)</label>
                        <textarea
                            id="description_ar"
                            name="description_ar"
                            rows="4"
                            dir="rtl"
                            class="input w-full @error('description_ar') border-red-400 @enderror"
                            placeholder="وصف قصير للعلامة التجارية…"
                        >{{ $val('description_ar') }}</textarea>
                        @error('description_ar') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- RIGHT: sidebar                                                  --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="w-72 flex-shrink-0 space-y-4">

            {{-- Save card ---------------------------------------------------- --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Save</h2>
                </div>
                <div class="px-5 py-5 space-y-3">
                    <button type="submit" class="btn btn-primary w-full" id="brand-save-btn">
                        <x-heroicon name="check" class="w-4 h-4 mr-1.5" />
                        {{ $isEdit ? 'Save Changes' : 'Create Brand' }}
                    </button>
                    <a href="{{ route('admin.brands.index') }}" class="btn btn-ghost w-full">
                        Cancel
                    </a>
                    @if($isEdit)
                    <div class="pt-1 border-t border-gray-100">
                        <p class="text-xs text-gray-400">
                            Created {{ $brand->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Toggles card ------------------------------------------------- --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-900">Settings</h2>
                </div>
                <div class="px-5 py-5 divide-y divide-gray-100">

                    {{-- is_active --}}
                    <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Active</p>
                            <p class="text-xs text-gray-500">Visible to customers</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_active" value="0">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="sr-only peer"
                                {{ $bool('is_active', true) ? 'checked' : '' }}
                            >
                            <div class="w-10 h-5 bg-gray-200 peer-checked:bg-primary-600 rounded-full
                                        after:absolute after:top-0.5 after:left-[2px]
                                        after:bg-white after:rounded-full after:h-4 after:w-4
                                        after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>

                    {{-- is_verified --}}
                    <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Verified</p>
                            <p class="text-xs text-gray-500">Official brand owner confirmed</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_verified" value="0">
                            <input
                                type="checkbox"
                                name="is_verified"
                                value="1"
                                class="sr-only peer"
                                {{ $bool('is_verified') ? 'checked' : '' }}
                            >
                            <div class="w-10 h-5 bg-gray-200 peer-checked:bg-success-600 rounded-full
                                        after:absolute after:top-0.5 after:left-[2px]
                                        after:bg-white after:rounded-full after:h-4 after:w-4
                                        after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>

                    {{-- is_restricted --}}
                    <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Restricted</p>
                            <p class="text-xs text-gray-500">Sellers require brand auth</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="is_restricted" value="0">
                            <input
                                type="checkbox"
                                name="is_restricted"
                                value="1"
                                class="sr-only peer"
                                {{ $bool('is_restricted') ? 'checked' : '' }}
                            >
                            <div class="w-10 h-5 bg-gray-200 peer-checked:bg-warning-500 rounded-full
                                        after:absolute after:top-0.5 after:left-[2px]
                                        after:bg-white after:rounded-full after:h-4 after:w-4
                                        after:transition-all peer-checked:after:translate-x-5"></div>
                        </label>
                    </div>

                </div>
            </div>

        </div>

    </div>{{-- /flex --}}

</div>{{-- /space-y-6 --}}
