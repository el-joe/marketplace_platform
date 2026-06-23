@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? ''" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <x-form.date-picker name="ends_at" label="Ends at" :value="$config['ends_at'] ?? null" enableTime />

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="background_color" type="color" label="Background" :value="$config['background_color'] ?? '#dc2626'" />
        <x-form.input name="text_color"       type="color" label="Text color" :value="$config['text_color']       ?? '#ffffff'" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
