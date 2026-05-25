{{--
    Shared Attribute form partial.
    Include with: @include('admin.attributes._form', ['mode' => 'create'])
                  @include('admin.attributes._form', ['mode' => 'edit', 'attribute' => $attribute])
--}}
@php
    $attribute = $attribute ?? null;
    $isEdit    = $attribute !== null;

    $val = function (string $field, $default = '') use ($isEdit, $attribute) {
        return old($field, $isEdit ? ($attribute->{$field} ?? $default) : $default);
    };

    $bool = function (string $field, bool $default = false) use ($isEdit, $attribute): bool {
        $raw = old($field, $isEdit ? ($attribute->{$field} ?? $default) : $default);
        return (bool) $raw;
    };

    $typeRequiresValues = $isEdit && in_array($attribute->type, ['select', 'multi_select', 'color']);
@endphp

<div
    x-data="{
        activeTab: 'general',
        attrType: '{{ $val('type', 'text') }}',
        get requiresValues() {
            return ['select', 'multi_select', 'color'].includes(this.attrType);
        }
    }"
    class="space-y-6"
>
    <input type="hidden" id="form-mode" name="_form_mode" value="{{ $isEdit ? 'edit' : 'create' }}">

    <div class="flex gap-6 items-start">

        {{-- ═══════════════════════════════════════════ --}}
        {{-- LEFT: Tabbed panels                         --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="flex-1 min-w-0">

            {{-- Tab navigation --}}
            <div class="bg-white rounded-t-xl border border-gray-200 overflow-hidden">
                <nav class="flex overflow-x-auto border-b border-gray-100" aria-label="Attribute form tabs">
                    @foreach([
                        ['id' => 'general', 'label' => 'General', 'icon' => 'information-circle'],
                        ['id' => 'values',  'label' => 'Values',  'icon' => 'list-bullet'],
                    ] as $tab)
                    <button
                        type="button"
                        @click="activeTab = '{{ $tab['id'] }}'"
                        :class="activeTab === '{{ $tab['id'] }}'
                            ? 'border-b-2 border-primary-600 text-primary-700 bg-primary-50/50'
                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                        class="flex items-center gap-1.5 px-4 py-3.5 text-sm font-medium -mb-px whitespace-nowrap transition-colors"
                        @if($tab['id'] === 'values') :class="!requiresValues && 'opacity-40 pointer-events-none'" @endif
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
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input
                        name="name_en"
                        label="Name (English)"
                        :value="$val('name_en')"
                        required
                        maxlength="255"
                        placeholder="e.g. Color"
                    />
                    <x-form.input
                        name="name_ar"
                        label="الاسم بالعربي"
                        :value="$val('name_ar')"
                        required
                        maxlength="255"
                        dir="rtl"
                        placeholder="مثال: اللون"
                    />
                </div>

                {{-- Code — locked after creation --}}
                <div>
                    <x-form.input
                        name="code"
                        label="Code"
                        :value="$val('code')"
                        :disabled="$isEdit"
                        required="{{ !$isEdit }}"
                        maxlength="100"
                        placeholder="lowercase_underscore"
                        pattern="[a-z_]+"
                    />
                    @if($isEdit)
                    <p class="text-xs text-gray-400 mt-1">Code cannot be changed after creation.</p>
                    @endif
                </div>

                {{-- Type — locked after creation --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="input w-full" x-model="attrType" @if($isEdit) disabled @endif>
                        <option value="text"         @selected($val('type') === 'text')>Text</option>
                        <option value="number"       @selected($val('type') === 'number')>Number</option>
                        <option value="select"       @selected($val('type') === 'select')>Select (single)</option>
                        <option value="multi_select" @selected($val('type') === 'multi_select')>Multi-select</option>
                        <option value="boolean"      @selected($val('type') === 'boolean')>Boolean (yes/no)</option>
                        <option value="color"        @selected($val('type') === 'color')>Color</option>
                        <option value="date"         @selected($val('type') === 'date')>Date</option>
                    </select>
                    @if($isEdit)
                    <p class="text-xs text-gray-400 mt-1">Type cannot be changed after creation.</p>
                    @endif
                </div>

                {{-- Unit --}}
                <x-form.input
                    name="unit"
                    label="Unit (optional)"
                    :value="$val('unit')"
                    maxlength="50"
                    placeholder="e.g. kg, cm, GB"
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

            {{-- TAB: Values (only enabled for select / multi_select / color) --}}
            <div
                x-show="activeTab === 'values'"
                class="bg-white rounded-b-xl border border-t-0 border-gray-200 p-6 shadow-sm space-y-4"
            >
                <div x-show="!requiresValues" class="text-sm text-gray-400 italic">
                    Values are only available for Select, Multi-select, and Color attribute types.
                </div>

                <div x-show="requiresValues" class="space-y-3">

                    {{-- On create: add rows inline --}}
                    @if(!$isEdit)
                    <div id="value-rows" class="space-y-2">
                        <div class="value-row grid grid-cols-12 gap-2 items-end">
                            <div class="col-span-4">
                                <label class="text-xs font-medium text-gray-600">Value (EN)</label>
                                <input type="text" name="values[0][value_en]" class="input w-full mt-1" placeholder="English" />
                            </div>
                            <div class="col-span-4">
                                <label class="text-xs font-medium text-gray-600">Value (AR)</label>
                                <input type="text" name="values[0][value_ar]" class="input w-full mt-1" placeholder="العربية" dir="rtl" />
                            </div>
                            <div class="col-span-3" x-show="attrType === 'color'">
                                <label class="text-xs font-medium text-gray-600">Hex Color</label>
                                <input type="color" name="values[0][color_hex]" class="w-full h-9 mt-1 rounded border border-gray-300 cursor-pointer" value="#000000" />
                            </div>
                            <div class="col-span-1 flex items-end pb-0.5">
                                <button type="button" class="remove-value-row text-gray-300 hover:text-red-500 transition-colors" title="Remove">
                                    <x-heroicon name="x-mark" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-value-row"
                        class="btn btn-ghost btn-sm text-primary-600">
                        <x-heroicon name="plus" class="w-4 h-4 mr-1" />
                        Add Value
                    </button>
                    @else
                    {{-- On edit: AJAX-managed list --}}
                    <div id="values-list" class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden">
                        @forelse($attribute->values as $val)
                        <div class="flex items-center gap-3 px-4 py-2.5 bg-white value-item" data-id="{{ $val->id }}">
                            <span class="flex-1 text-sm text-gray-800">{{ $val->value_en }}</span>
                            <span class="text-sm text-gray-400" dir="rtl">{{ $val->value_ar }}</span>
                            @if($attribute->type === 'color' && $val->color_hex)
                            <span class="w-5 h-5 rounded-full border border-gray-200 flex-shrink-0"
                                style="background:{{ $val->color_hex }}"></span>
                            @endif
                            <button type="button"
                                class="edit-value-btn text-xs text-primary-600 hover:underline"
                                data-id="{{ $val->id }}"
                                data-value-en="{{ $val->value_en }}"
                                data-value-ar="{{ $val->value_ar }}"
                                data-color-hex="{{ $val->color_hex }}">
                                Edit
                            </button>
                            <button type="button"
                                class="delete-value-btn text-xs text-red-500 hover:underline"
                                data-id="{{ $val->id }}"
                                data-url="{{ route('admin.attributes.values.destroy', [$attribute->id, $val->id]) }}">
                                Delete
                            </button>
                        </div>
                        @empty
                        <p class="px-4 py-3 text-sm text-gray-400 italic">No values yet.</p>
                        @endforelse
                    </div>

                    {{-- Add value form --}}
                    <div id="add-value-form" class="border border-dashed border-gray-300 rounded-lg p-4 space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Add New Value</p>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="text" id="new-value-en" class="input w-full" placeholder="English value" />
                            <input type="text" id="new-value-ar" class="input w-full" placeholder="قيمة بالعربي" dir="rtl" />
                        </div>
                        @if($attribute->type === 'color')
                        <div class="flex items-center gap-3">
                            <label class="text-xs font-medium text-gray-600">Hex Color</label>
                            <input type="color" id="new-color-hex" class="w-10 h-8 rounded border border-gray-300 cursor-pointer" value="#000000" />
                            <input type="text" id="new-color-hex-text" class="input w-28 text-xs" placeholder="#000000" maxlength="7" />
                        </div>
                        @endif
                        <button type="button" id="save-new-value" class="btn btn-primary btn-sm">Add Value</button>
                    </div>
                    @endif

                </div>
            </div>

        </div>{{-- /left column --}}

        {{-- ═══════════════════════════════════════════ --}}
        {{-- RIGHT SIDEBAR                               --}}
        {{-- ═══════════════════════════════════════════ --}}
        <div class="w-72 flex-shrink-0 space-y-4">

            {{-- Flags --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">Behaviour</h3>
                <x-form.toggle name="is_variant_attribute" label="Generates variants"   :checked="$bool('is_variant_attribute')" />
                <x-form.toggle name="is_filterable"        label="Show in filters"       :checked="$bool('is_filterable')" />
                <x-form.toggle name="is_required"          label="Required for products" :checked="$bool('is_required')" />
            </div>

            {{-- Save --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 space-y-2">
                @if($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-xs text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                    @endforeach
                </div>
                @endif

                <button type="submit" id="submit-btn" class="btn btn-primary w-full justify-center">
                    {{ $isEdit ? 'Save changes' : 'Create attribute' }}
                </button>
                <a href="{{ route('admin.attributes.index') }}"
                    class="btn btn-ghost w-full justify-center text-center block">
                    Cancel
                </a>
            </div>

        </div>{{-- /right sidebar --}}

    </div>{{-- /flex row --}}

</div>{{-- /x-data root --}}
