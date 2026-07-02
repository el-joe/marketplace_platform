@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <x-form.input name="link_url" label="Link URL" :value="$config['link_url'] ?? ''" />

    <x-form.select name="link_type" label="Link type" :value="$config['link_type'] ?? 'url'" class="mt-3">
        <option value="url">External URL</option>
        <option value="product">Product</option>
        <option value="category">Category</option>
        <option value="brand">Brand</option>
        <option value="flash_sale">Flash sale</option>
    </x-form.select>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="aspect_ratio"         label="Desktop ratio" :value="$config['aspect_ratio']        ?? '4:1'" />
        <x-form.input name="mobile_aspect_ratio"  label="Mobile ratio"  :value="$config['mobile_aspect_ratio'] ?? '2:1'" />
    </div>

    <div class="mt-4 text-xs text-gray-500">
        Upload the banner image via the
        <a href="#" class="text-primary-600 hover:underline" data-action="manage-ad-images" data-block-id="{{ $block?->id }}">Ad images panel</a>.
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
