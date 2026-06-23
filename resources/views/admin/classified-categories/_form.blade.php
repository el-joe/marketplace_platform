@php $old = fn($k,$d=null) => old($k, $category?->$k ?? $d); @endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name (English) *</label>
        <input type="text" name="name_en" value="{{ $old('name_en') }}" required
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm @error('name_en') border-red-400 @enderror">
        @error('name_en')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">الاسم (عربي) *</label>
        <input type="text" name="name_ar" value="{{ $old('name_ar') }}" required dir="rtl"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm @error('name_ar') border-red-400 @enderror">
        @error('name_ar')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
        <input type="text" name="slug" value="{{ $old('slug') }}"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <p class="text-xs text-gray-400 mt-0.5">Leave blank to auto-generate</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Icon (emoji or name)</label>
        <input type="text" name="icon" value="{{ $old('icon') }}" maxlength="50"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Parent Category</label>
        <select name="parent_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">— None —</option>
            @foreach($parents as $p)
            <option value="{{ $p->id }}" {{ $old('parent_id') == $p->id ? 'selected' : '' }}>
                {{ $p->name_en }}
            </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Contract Template</label>
        <select name="contract_template_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">— None —</option>
            @foreach($templates as $t)
            <option value="{{ $t->id }}" {{ $old('contract_template_id') == $t->id ? 'selected' : '' }}>
                {{ $t->name }}
            </option>
            @endforeach
        </select>
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Required Attachment Types</label>
    <input type="text" id="attach-types-input" placeholder="ownership_deed, id_copy, floor_plan"
           value="{{ implode(', ', $old('required_attachment_types', [])) }}"
           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <p class="text-xs text-gray-400 mt-0.5">Comma-separated. Each becomes a required upload field for the seller.</p>
    <div id="hidden-attach-types"></div>
</div>

<div class="flex flex-wrap gap-6">
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="requires_location_map" value="1" class="rounded"
               {{ $old('requires_location_map') ? 'checked' : '' }}>
        <span class="text-sm text-gray-700">Requires Location Map</span>
    </label>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="requires_sketch_upload" value="1" class="rounded"
               {{ $old('requires_sketch_upload') ? 'checked' : '' }}>
        <span class="text-sm text-gray-700">Requires Sketch Upload (كروكي)</span>
    </label>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" class="rounded"
               {{ $old('is_active', true) ? 'checked' : '' }}>
        <span class="text-sm text-gray-700">Active</span>
    </label>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
    <input type="number" name="sort_order" value="{{ $old('sort_order', 0) }}" min="0"
           class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-sm">
</div>

<script>
const attachInput = document.getElementById('attach-types-input');
const hiddenContainer = document.getElementById('hidden-attach-types');

function syncAttachTypes() {
    hiddenContainer.innerHTML = '';
    attachInput.value.split(',').map(s => s.trim()).filter(Boolean).forEach(type => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'required_attachment_types[]';
        inp.value = type;
        hiddenContainer.appendChild(inp);
    });
}
attachInput.addEventListener('input', syncAttachTypes);
syncAttachTypes();
</script>
