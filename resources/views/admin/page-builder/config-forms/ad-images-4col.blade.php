@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <x-form.select name="aspect_ratio" label="Aspect ratio" :value="$config['aspect_ratio'] ?? '1:1'" class="mt-3">
        <option value="1:1">1:1 (square)</option>
        <option value="4:3">4:3</option>
        <option value="3:4">3:4 (portrait)</option>
    </x-form.select>

    <div class="mt-4 text-xs text-gray-500">
        Manage the four images via the
        <a href="#" class="text-primary-600 hover:underline" data-action="manage-ad-images" data-block-id="{{ $block?->id }}">Ad images panel</a>.
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
