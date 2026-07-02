@extends('layouts.admin')

@section('title', 'Edit Packaging Supply')

@section('content')

    <div class="mb-6">
        <a href="{{ route('admin.packaging-supplies.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Supplies</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Edit Supply — {{ $supply->name_en }}</h1>
    </div>

    <div class="card max-w-2xl p-6">
        <form method="POST" action="{{ route('admin.packaging-supplies.update', $supply) }}" class="space-y-5">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Name (English)</label>
                    <input type="text" name="name_en" value="{{ old('name_en', $supply->name_en) }}"
                           class="input @error('name_en') border-red-400 @enderror" required>
                    @error('name_en') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Name (Arabic)</label>
                    <input type="text" name="name_ar" value="{{ old('name_ar', $supply->name_ar) }}"
                           class="input text-right font-arabic @error('name_ar') border-red-400 @enderror" dir="rtl" required>
                    @error('name_ar') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Type</label>
                    <select name="type" class="input @error('type') border-red-400 @enderror" required>
                        @foreach(['box','bag','tape','label','other'] as $t)
                            <option value="{{ $t }}" @selected(old('type', $supply->type) === $t)>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Size <span class="text-gray-400">(optional)</span></label>
                    <input type="text" name="size" value="{{ old('size', $supply->size) }}"
                           class="input @error('size') border-red-400 @enderror">
                    @error('size') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Unit Cost (cents)</label>
                    <input type="number" name="unit_cost_cents" value="{{ old('unit_cost_cents', $supply->unit_cost_cents) }}"
                           min="0" class="input @error('unit_cost_cents') border-red-400 @enderror" required>
                    @error('unit_cost_cents') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Stock Available <span class="text-gray-400">(blank = unlimited)</span></label>
                    <input type="number" name="stock_available" value="{{ old('stock_available', $supply->stock_available) }}"
                           min="0" class="input @error('stock_available') border-red-400 @enderror">
                    @error('stock_available') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="label">Image Path <span class="text-gray-400">(optional)</span></label>
                <input type="text" name="image_path" value="{{ old('image_path', $supply->image_path) }}"
                       class="input @error('image_path') border-red-400 @enderror">
                @error('image_path') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       @checked(old('is_active', $supply->is_active)) class="rounded border-gray-300 text-primary-600">
                <label for="is_active" class="text-sm text-gray-700">Active (visible to vendors)</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.packaging-supplies.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

@endsection
