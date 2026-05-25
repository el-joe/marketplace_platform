{{--
    Shared Category form partial.
    Include with: @include('admin.categories._form', ['mode' => 'create'])
                  @include('admin.categories._form', ['mode' => 'edit', 'category' => $category])

    Parent view must wrap this in a <form> tag with proper action / @csrf / @method.
--}}
@php
    $category = $category ?? null;
    $isEdit = $category !== null;

    $val = function (string $field, $default = '') use ($isEdit, $category) {
        return old($field, $isEdit ? ($category->{$field} ?? $default) : $default);
    };

    $bool = function (string $field, bool $default = false) use ($isEdit, $category): bool {
        $raw = old($field, $isEdit ? ($category->{$field} ?? $default) : $default);
        return (bool) $raw;
    };
@endphp

<div
    x-data="{ activeTab: 'general' }"
    class="space-y-6"
>
    <input type="hidden" id="form-mode" name="_form_mode" value="{{ $isEdit ? 'edit' : 'create' }}">

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════ --}}
        {{-- LEFT COLUMN: tabbed panels                  --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0">

            {{-- Tab navigation --}}
            <div class="bg-white rounded-t-xl border border-gray-200 overflow-hidden">
                <nav class="flex overflow-x-auto border-b border-gray-100" aria-label="Category form tabs">
                    @foreach([
                        ['id' => 'general',    'label' => 'General',    'icon' => 'information-circle'],
                        ['id' => 'attributes', 'label' => 'Attributes', 'icon' => 'tag'],
                        ['id' => 'seo',        'label' => 'SEO',        'icon' => 'magnifying-glass'],
                    ] as $tab)
                    <button
                        type="button"
                        @click="activeTab = '{{ $tab['id'] }}'"
                        :class="activeTab === '{{ $tab['id'] }}'
                            ? 'border-b-2 border-primary-600 text-primary-700 bg-primary-50/50'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="flex items-center gap-1.5 px-4 py-3.5 text-sm font-medium -mb-px whitespace-nowrap transition-colors"
                    >
                        <x-heroicon name="{{ $tab['icon'] }}" class="w-4 h-4" />
                        {{ $tab['label'] }}
                    </button>
                    @endforeach
                </nav>
            </div>

            {{-- TAB: General --}}
            <div
                x-show="activeTab === 'general'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
            >
                {{-- Names --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input
                        name="name_en"
                        label="Name (English)"
                        :value="$val('name_en')"
                        required
                        maxlength="255"
                        placeholder="e.g. Electronics"
                    />
                    <x-form.input
                        name="name_ar"
                        label="الاسم بالعربي"
                        :value="$val('name_ar')"
                        required
                        maxlength="255"
                        dir="rtl"
                        placeholder="مثال: إلكترونيات"
                    />
                </div>

                {{-- Slug --}}
                <x-form.input
                    name="slug"
                    label="Slug"
                    :value="$val('slug')"
                    maxlength="255"
                    placeholder="auto-generated"
                    data-slug-input
                    data-slug-source="name_en"
                />

                {{-- Parent --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parent Category</label>
                    <select name="parent_id" class="input w-full">
                        <option value="">— Root (no parent) —</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent['id'] }}"
                                @selected(old('parent_id', $isEdit ? $category->parent_id : '') == $parent['id'])>
                                {!! e($parent['name']) !!}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Descriptions --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-form.textarea
                        name="description_en"
                        label="Description (English)"
                        :value="$val('description_en')"
                        rows="3"
                        maxlength="2000"
                    />
                    <x-form.textarea
                        name="description_ar"
                        label="الوصف بالعربي"
                        :value="$val('description_ar')"
                        rows="3"
                        maxlength="2000"
                        dir="rtl"
                    />
                </div>

                {{-- Commission Rate --}}
                <x-form.input
                    name="commission_rate"
                    label="Commission Rate (%)"
                    type="number"
                    :value="$val('commission_rate', '0.00')"
                    min="0" max="100" step="0.01"
                    placeholder="0.00"
                />

                {{-- Sort Order --}}
                <x-form.input
                    name="sort_order"
                    label="Sort Order"
                    type="number"
                    :value="$val('sort_order', '0')"
                    min="0"
                />
            </div>

            {{-- TAB: Attributes --}}
            <div
                x-show="activeTab === 'attributes'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-4"
            >
                <p class="text-sm text-gray-500">
                    Select which attributes vendors must fill when listing a product in this category.
                </p>

                @if($allAttributes->isEmpty())
                    <p class="text-sm text-gray-400 italic">No attributes defined yet. <a href="{{ route('admin.attributes.create') }}" class="text-primary-600 underline">Create attributes</a> first.</p>
                @else
                    <div class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                        @foreach($allAttributes as $attr)
                        @php
                            $assigned = $isEdit
                                ? $category->categoryAttributes->firstWhere('attribute_id', $attr->id)
                                : null;
                        @endphp
                        <div class="flex items-center gap-4 px-4 py-3 bg-white hover:bg-gray-50">
                            <input type="checkbox"
                                name="attributes[{{ $loop->index }}][attribute_id]"
                                value="{{ $attr->id }}"
                                id="attr_{{ $attr->id }}"
                                class="attr-assign-cb rounded border-gray-300 text-primary-600"
                                @checked($assigned !== null) />
                            <input type="hidden" name="attributes[{{ $loop->index }}][attribute_id]" value="{{ $attr->id }}" disabled />
                            <label for="attr_{{ $attr->id }}" class="flex-1 cursor-pointer">
                                <span class="text-sm font-medium text-gray-900">{{ $attr->name_en }}</span>
                                <span class="ml-2 text-xs text-gray-400">{{ $attr->code }}</span>
                                <span class="ml-1 text-xs px-1.5 py-0.5 bg-gray-100 rounded text-gray-500">{{ $attr->type }}</span>
                            </label>
                            <label class="flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                                <input type="checkbox"
                                    name="attributes[{{ $loop->index }}][is_required]"
                                    value="1"
                                    class="rounded border-gray-300 text-primary-600"
                                    @checked($assigned?->is_required) />
                                Required
                            </label>
                            <input type="hidden" name="attributes[{{ $loop->index }}][sort_order]" value="{{ $loop->index }}" />
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- TAB: SEO --}}
            <div
                x-show="activeTab === 'seo'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
            >
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input
                        name="seo_title_en"
                        label="SEO Title (English)"
                        :value="$val('seo_title_en')"
                        maxlength="70"
                        placeholder="Leave blank to auto-generate"
                    />
                    <x-form.input
                        name="seo_title_ar"
                        label="عنوان SEO بالعربي"
                        :value="$val('seo_title_ar')"
                        maxlength="70"
                        dir="rtl"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-form.textarea
                        name="seo_description_en"
                        label="SEO Description (English)"
                        :value="$val('seo_description_en')"
                        rows="3"
                        maxlength="160"
                    />
                    <x-form.textarea
                        name="seo_description_ar"
                        label="وصف SEO بالعربي"
                        :value="$val('seo_description_ar')"
                        rows="3"
                        maxlength="160"
                        dir="rtl"
                    />
                </div>
            </div>

        </div>{{-- /left column --}}

        {{-- ═══════════════════════════════════════════ --}}
        {{-- RIGHT SIDEBAR                               --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="w-72 flex-shrink-0 space-y-4">

            {{-- Visibility --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">Visibility</h3>
                <x-form.toggle name="is_active"  label="Active"  :value="$bool('is_active', true)" />
                <x-form.toggle name="is_visible" label="Visible" :value="$bool('is_visible', true)" />
                <x-form.toggle name="is_featured" label="Featured (homepage)" :value="$bool('is_featured')" />
            </div>

            {{-- Save actions --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2">
                @if($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-xs text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                    @endforeach
                </div>
                @endif

                <button type="submit" id="submit-btn"
                    class="btn btn-primary w-full justify-center">
                    {{ $isEdit ? 'Save changes' : 'Create category' }}
                </button>
                <a href="{{ route('admin.categories.index') }}"
                    class="btn btn-ghost w-full justify-center text-center block">
                    Cancel
                </a>
            </div>

        </div>{{-- /right sidebar --}}

    </div>{{-- /flex row --}}

</div>{{-- /x-data root --}}
