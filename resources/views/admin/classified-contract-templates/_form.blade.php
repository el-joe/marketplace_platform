@php $old = fn($k,$d=null) => old($k, $template?->$k ?? $d); @endphp

<div class="grid grid-cols-2 gap-4">
    <div class="col-span-2 sm:col-span-1">
        <label class="block text-sm font-medium text-gray-700 mb-1">Template Name *</label>
        <input type="text" name="name" value="{{ $old('name') }}" required
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Version</label>
        <input type="number" name="version" value="{{ $old('version', 1) }}" min="1"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Category (optional)</label>
    <select name="classified_category_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">— General / All —</option>
        @foreach($categories as $cat)
        <option value="{{ $cat->id }}" {{ $old('classified_category_id') == $cat->id ? 'selected' : '' }}>
            {{ $cat->name_en }}
        </option>
        @endforeach
    </select>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Contract Content (English) *</label>
    <textarea name="content_en" rows="10" required
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono">{{ $old('content_en') }}</textarea>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">محتوى العقد (عربي) *</label>
    <textarea name="content_ar" rows="10" required dir="rtl"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono">{{ $old('content_ar') }}</textarea>
</div>

<div class="flex items-center gap-2">
    <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded"
           {{ $old('is_active', true) ? 'checked' : '' }}>
    <label for="is_active" class="text-sm text-gray-700">Active</label>
</div>
