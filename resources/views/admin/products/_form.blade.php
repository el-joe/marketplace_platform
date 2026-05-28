{{--
    Shared Product form partial.
    Include with: @include('admin.products._form', ['mode' => 'create'])
                  @include('admin.products._form', ['mode' => 'edit', 'product' => $product])

    Parent view must wrap this in a <form> tag with proper action / @csrf / @method.
--}}
@php
    $product = $product ?? null;
    $isEdit = $product !== null;

    /** Quick helper to resolve old() → product field → default. */
    $val = function (string $field, $default = '') use ($isEdit, $product) {
        return old($field, $isEdit ? ($product->{$field} ?? $default) : $default);
    };

    /** Boolean helper for checkboxes / toggles. */
    $bool = function (string $field, bool $default = false) use ($isEdit, $product): bool {
        $raw = old($field, $isEdit ? ($product->{$field} ?? $default) : $default);
        return (bool) $raw;
    };
@endphp

{{-- ─────────────────────────────────────────────────────────────────────────── --}}
{{-- Alpine root: one reactive component for the whole form                     --}}
{{-- ─────────────────────────────────────────────────────────────────────────── --}}
<div
    x-data="{
        activeTab:       'basic',
        hasVariants:     {{ $bool('has_variants') ? 'true' : 'false' }},
        isAgeRestricted: {{ $bool('is_age_restricted') ? 'true' : 'false' }},
        showDuplicate:   false,
        duplicateProduct: null,
    }"
    class="space-y-6"
>
    <input type="hidden" id="form-mode" name="_form_mode" value="{{ $isEdit ? 'edit' : 'create' }}">

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- LEFT COLUMN: tabbed panels                                         --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0">

            {{-- Tab navigation bar --}}
            <div class="bg-white rounded-t-xl border border-gray-200 overflow-hidden">
                <nav class="flex overflow-x-auto border-b border-gray-100" aria-label="Product form tabs">
                    @foreach([
                        ['id' => 'basic',     'label' => 'Basic Info',  'icon' => 'information-circle'],
                        ['id' => 'content',   'label' => 'Content',     'icon' => 'document-text'],
                        ['id' => 'variants',  'label' => 'Variants',    'icon' => 'cube'],
                        ['id' => 'images',    'label' => 'Images',      'icon' => 'photo'],
                        ['id' => 'countries', 'label' => 'Countries',   'icon' => 'globe-alt'],
                        ['id' => 'seo',       'label' => 'SEO',         'icon' => 'magnifying-glass'],
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

            {{-- ─────────────────────────────────────────────── --}}
            {{-- TAB: Basic Info                                 --}}
            {{-- ─────────────────────────────────────────────── --}}
            <div
                x-show="activeTab === 'basic'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
            >
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input
                        name="name_en"
                        label="Name (English)"
                        :value="$val('name_en')"
                        required
                        maxlength="255"
                        placeholder="e.g. Apple iPhone 15 Pro"
                    />
                    <x-form.input
                        name="name_ar"
                        label="الاسم بالعربي"
                        :value="$val('name_ar')"
                        required
                        maxlength="255"
                        dir="rtl"
                        placeholder="مثال: آيفون 15 برو"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-form.async-select
                        name="category_id"
                        label="Category"
                        required
                        search-url="{{ route('admin.categories.search') }}"
                        placeholder="Search categories…"
                        :min-length="0"
                        :value="$isEdit ? $product->category_id : null"
                        :value-label="$isEdit && $product->category_id ? ($categories[$product->category_id] ?? '') : null"
                    />
                    <x-form.async-select
                        name="brand_id"
                        label="Brand"
                        search-url="{{ route('admin.brands.search') }}"
                        placeholder="Search brands…"
                        :min-length="0"
                        :value="$isEdit ? $product->brand_id : null"
                        :value-label="$isEdit && $product->brand_id ? ($brands[$product->brand_id] ?? '') : null"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.input
                            name="gtin"
                            id="gtin-input"
                            label="Barcode (EAN-13 / UPC)"
                            :value="$val('gtin')"
                            maxlength="13"
                            placeholder="13-digit barcode"
                            help="Leave blank if no barcode. Must be exactly 13 digits."
                        />
                        {{-- Duplicate warning injected by JS --}}
                        <div id="gtin-warning" class="hidden mt-2 rounded-lg bg-warning-50 border border-warning-200 p-3 text-sm text-warning-800"></div>
                    </div>
                    <x-form.input
                        name="model_number"
                        label="Model Number"
                        :value="$val('model_number')"
                        maxlength="100"
                        placeholder="e.g. MQ9T3LL/A"
                    />
                </div>
            </div>

            {{-- ─────────────────────────────────────────────── --}}
            {{-- TAB: Content                                    --}}
            {{-- ─────────────────────────────────────────────── --}}
            <div
                x-show="activeTab === 'content'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
            >
                <x-form.rich-editor
                    name="description_en"
                    label="Description (English)"
                    :value="$val('description_en')"
                />
                <x-form.rich-editor
                    name="description_ar"
                    label="وصف المنتج بالعربية"
                    :value="$val('description_ar')"
                />
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="form-label">Short Description (EN)</label>
                            <span class="text-xs text-gray-400" data-char-counter="short_desc_en" data-max="500">0 / 500</span>
                        </div>
                        <textarea name="short_desc_en" id="short_desc_en"
                            maxlength="500" rows="4"
                            class="form-textarea w-full @error('short_desc_en') is-invalid @enderror"
                            placeholder="Brief product summary…">{{ $val('short_desc_en') }}</textarea>
                        @error('short_desc_en') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="form-label" dir="rtl">وصف قصير بالعربية</label>
                            <span class="text-xs text-gray-400" data-char-counter="short_desc_ar" data-max="500">0 / 500</span>
                        </div>
                        <textarea name="short_desc_ar" id="short_desc_ar"
                            maxlength="500" rows="4" dir="rtl"
                            class="form-textarea w-full @error('short_desc_ar') is-invalid @enderror"
                            placeholder="ملخص قصير…">{{ $val('short_desc_ar') }}</textarea>
                        @error('short_desc_ar') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────────────────────────── --}}
            {{-- TAB: Variants                                   --}}
            {{-- ─────────────────────────────────────────────── --}}
            <div
                x-show="activeTab === 'variants'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
            >
                <div x-show="!hasVariants" class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-700 flex items-start gap-2">
                    <x-heroicon name="information-circle" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span>Enable <strong>Has Variants</strong> in the Classification card (right sidebar) to configure product variants.</span>
                </div>

                <div x-show="hasVariants" class="space-y-5">
                    {{-- Attribute checkboxes --}}
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Variant Attributes</h4>
                        <div id="variant-attributes-container" class="grid grid-cols-3 gap-3">
                            @foreach($categoryAttributes ?? [] as $attr)
                            <label class="flex items-center gap-2 px-3 py-2.5 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 text-sm">
                                <input
                                    type="checkbox"
                                    name="variant_attributes[]"
                                    value="{{ $attr->id }}"
                                    class="rounded border-gray-300 text-primary-600 variant-attr-cb"
                                    {{ in_array($attr->id, $existingAttrValues ?? []) ? 'checked' : '' }}
                                />
                                {{ $attr->name_en }}
                            </label>
                            @endforeach
                        </div>
                        @if(empty($categoryAttributes))
                        <p class="text-sm text-gray-400 italic" id="no-attrs-msg">
                            Select a category first. Variant attributes load automatically.
                        </p>
                        @endif
                    </div>

                    {{-- Generate button --}}
                    <button type="button" id="generate-variants-btn"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-primary-300 bg-primary-50 text-primary-700 text-sm font-medium hover:bg-primary-100 transition-colors">
                        <x-heroicon name="cube" class="w-4 h-4" />
                        Generate combinations
                    </button>

                    {{-- Variants table --}}
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full text-sm" id="variants-table">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Variant</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Barcode</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Weight (g)</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-20">Default</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Active</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="variants-tbody" class="divide-y divide-gray-100">
                                @if($isEdit)
                                @foreach($variants ?? [] as $vi => $variant)
                                <tr class="variant-row hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-800">
                                        {{ $variant->name ?? 'Default variant' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="variants[{{ $vi }}][sku]"
                                            value="{{ $variant->sku }}"
                                            class="form-input text-sm py-1.5 w-full" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="variants[{{ $vi }}][barcode]"
                                            value="{{ $variant->barcode }}"
                                            class="form-input text-sm py-1.5 w-full" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="variants[{{ $vi }}][weight_grams]"
                                            value="{{ $variant->weight_grams }}" min="0"
                                            class="form-input text-sm py-1.5 w-full" />
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="radio" name="variants_default" value="{{ $vi }}"
                                            class="text-primary-600 border-gray-300"
                                            {{ $variant->is_default ? 'checked' : '' }} />
                                        <input type="hidden" name="variants[{{ $vi }}][is_default]"
                                            value="{{ $variant->is_default ? '1' : '0' }}" class="default-flag" />
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" name="variants[{{ $vi }}][is_active]" value="1"
                                            class="rounded text-primary-600 border-gray-300"
                                            {{ $variant->is_active ? 'checked' : '' }} />
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button" class="remove-variant-row text-gray-400 hover:text-red-600 transition-colors" title="Remove">
                                            <x-heroicon name="x-circle" class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                        <div id="no-variants-msg" class="{{ ($isEdit && count($variants ?? []) > 0) ? 'hidden' : '' }} px-4 py-6 text-center text-sm text-gray-400">
                            No variants yet. Select attributes above and click <em>Generate combinations</em>.
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────────────────────────── --}}
            {{-- TAB: Images                                     --}}
            {{-- ─────────────────────────────────────────────── --}}
            <div
                x-show="activeTab === 'images'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-4"
            >
                <p class="text-sm text-gray-500">
                    Upload product images. The <strong>first image</strong> will be set as the primary image.
                    Drag to reorder. Max 5 MB per image.
                </p>

                {{-- FilePond mount point — initialized in products.js --}}
                <input
                    type="file"
                    id="product-images-filepond"
                    name="images[]"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                    data-process-field="file"
                    data-upload-url="{{ route('admin.products.upload-image') }}"
                    data-revert-base="{{ Str::beforeLast(route('admin.products.delete-image', ['mediaId' => '__id__']), '/__id__') }}"
                />
                {{-- Existing images (edit mode) rendered as FilePond mock files via JS --}}
                @if($isEdit && isset($images) && count($images) > 0)
                @php
                    $imagesMap = $images->map(fn($img) => [
                        'id'   => $img->id,
                        'url'  => \Illuminate\Support\Facades\Storage::url($img->path),
                        'name' => basename($img->path),
                        'size' => $img->size_bytes,
                        'mime_type' => $img->mime_type,
                    ])->values()->all();
                @endphp
                <script>
                    window.existingProductImages = @json($imagesMap ?? []);
                </script>
                @else
                <script>window.existingProductImages = [];</script>
                @endif
            </div>

            {{-- ─────────────────────────────────────────────── --}}
            {{-- TAB: Countries                                  --}}
            {{-- ─────────────────────────────────────────────── --}}
            <div
                x-show="activeTab === 'countries'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 shadow-sm"
            >
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h4 class="text-sm font-semibold text-gray-700">Country Availability</h4>
                    <div class="flex gap-2">
                        <button type="button" id="enable-all-countries"
                            class="text-xs px-3 py-1.5 rounded-md border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 font-medium">
                            Enable all
                        </button>
                        <button type="button" id="disable-all-countries"
                            class="text-xs px-3 py-1.5 rounded-md border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 font-medium">
                            Disable all
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Country</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-28">Available</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name Override</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Cert Required</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($countries ?? [] as $country)
                            @php
                                $cs = isset($countrySettings) ? ($countrySettings[$country->id] ?? null) : null;
                                $cAvailable    = (bool) old("countries.{$country->id}.is_available",  $cs?->is_available  ?? true);
                                $cNameOverride = old("countries.{$country->id}.name_override_en",    $cs?->name_override_en ?? '');
                                $cCert         = (bool) old("countries.{$country->id}.requires_local_cert", $cs?->requires_local_cert ?? false);
                            @endphp
                            <tr class="hover:bg-gray-50 country-row" x-data="{ avail: {{ $cAvailable ? 'true' : 'false' }} }">
                                <td class="px-6 py-3 font-medium text-gray-900">
                                    <input type="hidden" name="countries[{{ $country->id }}][country_id]" value="{{ $country->id }}">
                                    {{ $country->name_en }}
                                    <span class="text-xs text-gray-400 font-normal ml-1">
                                        ({{ $country->iso_code_2 ?? $country->iso_code_3 ?? '' }})
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input type="hidden" name="countries[{{ $country->id }}][is_available]" value="0" class="country-avail-hidden">
                                    <input type="checkbox"
                                        name="countries[{{ $country->id }}][is_available]"
                                        value="1"
                                        x-model="avail"
                                        class="rounded text-primary-600 border-gray-300 w-4 h-4 country-avail-cb"
                                        {{ $cAvailable ? 'checked' : '' }}
                                    />
                                </td>
                                <td class="px-6 py-3">
                                    <input type="text"
                                        name="countries[{{ $country->id }}][name_override_en]"
                                        value="{{ $cNameOverride }}"
                                        :disabled="!avail"
                                        placeholder="Same as default"
                                        class="form-input text-sm py-1.5 w-full disabled:opacity-40 disabled:cursor-not-allowed"
                                    />
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <input type="hidden" name="countries[{{ $country->id }}][requires_local_cert]" value="0">
                                    <input type="checkbox"
                                        name="countries[{{ $country->id }}][requires_local_cert]"
                                        value="1"
                                        class="rounded text-primary-600 border-gray-300 w-4 h-4"
                                        {{ $cCert ? 'checked' : '' }}
                                    />
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400 italic">
                                    No launched countries configured yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ─────────────────────────────────────────────── --}}
            {{-- TAB: SEO                                        --}}
            {{-- ─────────────────────────────────────────────── --}}
            <div
                x-show="activeTab === 'seo'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-5"
            >
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="seo_title" class="form-label">SEO Title</label>
                        <span class="text-xs text-gray-400" data-char-counter="seo_title" data-max="70">0 / 70</span>
                    </div>
                    <input type="text" name="seo_title" id="seo_title"
                        value="{{ $val('seo_title') }}" maxlength="70"
                        class="form-input w-full @error('seo_title') is-invalid @enderror"
                        placeholder="Appears in browser tab &amp; search results…"
                    />
                    @error('seo_title') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="seo_description" class="form-label">SEO Description</label>
                        <span class="text-xs text-gray-400" data-char-counter="seo_description" data-max="160">0 / 160</span>
                    </div>
                    <textarea name="seo_description" id="seo_description"
                        maxlength="160" rows="3"
                        class="form-textarea w-full @error('seo_description') is-invalid @enderror"
                        placeholder="Brief description for search engines (max 160 chars)…">{{ $val('seo_description') }}</textarea>
                    @error('seo_description') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <x-form.slug-input
                    name="slug"
                    label="URL Slug"
                    source-field="name_en"
                    :value="$val('slug')"
                    :prefix="rtrim(config('app.url'), '/') . '/products/'"
                />

                {{-- SEO preview card --}}
                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Search Preview</p>
                    <p id="seo-preview-title" class="text-base font-medium text-blue-700 hover:underline cursor-pointer truncate">
                        {{ $val('seo_title') ?: $val('name_en') ?: 'Product title' }}
                    </p>
                    <p class="text-xs text-green-700 mb-1">{{ rtrim(config('app.url'), '/') }}/products/<span id="seo-preview-slug">{{ $val('slug') ?: 'product-slug' }}</span></p>
                    <p id="seo-preview-desc" class="text-sm text-gray-600 line-clamp-2">
                        {{ $val('seo_description') ?: 'Add a meta description to improve search engine visibility.' }}
                    </p>
                </div>
            </div>

        </div>{{-- /left column --}}

        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        {{-- RIGHT SIDEBAR: sticky                                              --}}
        {{-- ═══════════════════════════════════════════════════════════════════ --}}
        <div class="w-72 flex-shrink-0 sticky top-20 space-y-4">

            {{-- Status card --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-800">Status</h3>
                <x-form.select
                    name="status"
                    label=""
                    :value="$val('status', 'draft')"
                    :options="[
                        'draft'        => 'Draft',
                        'active'       => 'Active',
                        'discontinued' => 'Discontinued',
                        'restricted'   => 'Restricted',
                    ]"
                />
                @if($isEdit)
                <div class="pt-2 border-t border-gray-100 space-y-1.5 text-xs text-gray-500">
                    <div class="flex justify-between">
                        <span>Created</span>
                        <span>{{ \Carbon\Carbon::parse($product->created_at)->format('M j, Y') }}</span>
                    </div>
                    @if($product->updated_at && $product->updated_at !== $product->created_at)
                    <div class="flex justify-between">
                        <span>Updated</span>
                        <span>{{ \Carbon\Carbon::parse($product->updated_at)->format('M j, Y') }}</span>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Classification card --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-1">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Classification</h3>

                {{-- has_variants — synced to Alpine hasVariants --}}
                <label class="flex items-center gap-3 py-2 cursor-pointer select-none w-full group">
                    <input type="hidden" name="has_variants" value="0">
                    <input type="checkbox" name="has_variants" id="has_variants" value="1"
                        class="sr-only" x-model="hasVariants"
                        {{ $bool('has_variants') ? 'checked' : '' }}>
                    <div class="relative w-11 h-6 rounded-full transition-colors duration-200 flex-shrink-0"
                        :class="hasVariants ? 'bg-primary-600' : 'bg-gray-200'">
                        <span class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                            :class="hasVariants ? 'translate-x-5' : 'translate-x-0'"></span>
                    </div>
                    <span class="text-sm text-gray-700 group-hover:text-gray-900">Has variants</span>
                </label>

                <x-form.toggle name="is_featured"         label="Featured on homepage" :value="$bool('is_featured')" />
                <x-form.toggle name="requires_brand_auth" label="Requires brand auth"  :value="$bool('requires_brand_auth')" />
                <x-form.toggle name="is_hazardous"        label="Hazardous item"       :value="$bool('is_hazardous')" />

                {{-- is_age_restricted — synced to Alpine isAgeRestricted --}}
                <label class="flex items-center gap-3 py-2 cursor-pointer select-none w-full group">
                    <input type="hidden" name="is_age_restricted" value="0">
                    <input type="checkbox" name="is_age_restricted" id="is_age_restricted" value="1"
                        class="sr-only" x-model="isAgeRestricted"
                        {{ $bool('is_age_restricted') ? 'checked' : '' }}>
                    <div class="relative w-11 h-6 rounded-full transition-colors duration-200 flex-shrink-0"
                        :class="isAgeRestricted ? 'bg-primary-600' : 'bg-gray-200'">
                        <span class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow transition-transform duration-200"
                            :class="isAgeRestricted ? 'translate-x-5' : 'translate-x-0'"></span>
                    </div>
                    <span class="text-sm text-gray-700 group-hover:text-gray-900">Age restricted</span>
                </label>

                <div x-show="isAgeRestricted" x-cloak class="mt-1 pl-14">
                    <x-form.input
                        name="min_age"
                        label="Minimum age"
                        type="number"
                        :value="$val('min_age')"
                        min="1" max="99"
                    />
                </div>
            </div>

            {{-- Save actions card --}}
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
                    {{ $isEdit ? 'Save changes' : 'Create product' }}
                </button>
                <a href="{{ route('admin.products.index') }}"
                    class="btn btn-ghost w-full justify-center text-center block">
                    Cancel
                </a>
            </div>

        </div>{{-- /right sidebar --}}

    </div>{{-- /flex row --}}

    {{-- ─────────────────────────────────────────────────────────────── --}}
    {{-- GTIN Duplicate warning modal                                   --}}
    {{-- ─────────────────────────────────────────────────────────────── --}}
    <div x-show="showDuplicate" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50" @click="showDuplicate = false"></div>
        <div class="relative bg-white rounded-xl shadow-2xl p-6 max-w-md w-full">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <x-heroicon name="exclamation-triangle" class="w-5 h-5 text-amber-600" />
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900">Duplicate Barcode Found</h3>
                    <p class="text-sm text-gray-600 mt-1">This GTIN is already used by:</p>
                    <div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <p class="text-sm font-medium text-gray-900" x-text="duplicateProduct?.name_en"></p>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="'Status: ' + (duplicateProduct?.status ?? '')"></p>
                    </div>
                    <div class="flex gap-2 mt-4">
                        <button type="button" @click="showDuplicate = false"
                            class="btn btn-ghost btn-sm flex-1">
                            Continue anyway
                        </button>
                        <a :href="duplicateProduct?.url" target="_blank"
                            class="btn btn-primary btn-sm flex-1 text-center">
                            View product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /x-data root --}}
