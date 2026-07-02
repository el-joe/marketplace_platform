@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? 'Join our newsletter'" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.textarea name="subtitle_en" label="Subtitle (EN)" rows="2" :value="$config['subtitle_en'] ?? ''" />
        <x-form.textarea name="subtitle_ar" label="Subtitle (AR)" rows="2" :value="$config['subtitle_ar'] ?? ''" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
