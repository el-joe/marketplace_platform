@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
        <ul class="list-disc ps-4">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="form-label">Name (English)</label>
        <input type="text" name="name_en" value="{{ old('name_en', $product->name_en ?? '') }}" class="form-input w-full" required>
    </div>
    <div>
        <label class="form-label">Name (Arabic)</label>
        <input type="text" name="name_ar" value="{{ old('name_ar', $product->name_ar ?? '') }}" class="form-input w-full" required>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="form-label">Description (English)</label>
        <textarea name="description_en" rows="3" class="form-input w-full">{{ old('description_en', $product->description_en ?? '') }}</textarea>
    </div>
    <div>
        <label class="form-label">Description (Arabic)</label>
        <textarea name="description_ar" rows="3" class="form-input w-full">{{ old('description_ar', $product->description_ar ?? '') }}</textarea>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
    <div>
        <label class="form-label">Price</label>
        <input type="number" name="price" min="1" value="{{ old('price', $product->price ?? '') }}" class="form-input w-full" required>
    </div>
    <div>
        <label class="form-label">Currency</label>
        <input type="text" name="currency" maxlength="3" value="{{ old('currency', $product->currency ?? '') }}" class="form-input w-full uppercase" placeholder="EGP" required>
    </div>
    <div>
        <label class="form-label">Stock Quantity</label>
        <input type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" class="form-input w-full" required>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="form-label">
            Platform Commission Rate (basis points, {{ number_format($minRate / 100, 2) }}% minimum)
        </label>
        <input type="number" name="platform_commission_rate" min="{{ $minRate }}"
            value="{{ old('platform_commission_rate', $product->platform_commission_rate ?? $minRate) }}" class="form-input w-full" required>
        <p class="text-xs text-gray-400 mt-1">e.g. 500 = 5.00% given to the platform; you keep the rest.</p>
    </div>
    <div>
        <label class="form-label">SKU</label>
        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" class="form-input w-full">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="form-label">Weight (grams)</label>
        <input type="number" name="weight_grams" min="0" value="{{ old('weight_grams', $product->weight_grams ?? '') }}" class="form-input w-full">
    </div>
    <div class="flex items-center gap-2 mt-6">
        <input type="checkbox" name="is_digital" value="1" id="is_digital" {{ old('is_digital', $product->is_digital ?? false) ? 'checked' : '' }}>
        <label for="is_digital" class="form-label mb-0">Digital product</label>
    </div>
</div>

<div class="mt-4">
    <label class="form-label">Image</label>
    @if(!empty($product?->image_path))
        <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" class="w-24 h-24 object-cover rounded-lg mb-2" alt="">
    @endif
    <input type="file" name="image" accept="image/*" class="form-input w-full">
</div>
