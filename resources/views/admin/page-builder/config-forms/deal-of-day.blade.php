@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="title_en" label="Title (EN)" :value="$config['title_en'] ?? 'Deal of the Day'" />
        <x-form.input name="title_ar" label="Title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <x-form.date-picker name="ends_at" label="Deal ends at" enableTime :value="$config['ends_at'] ?? ''" class="mt-3" />

    <x-form.select name="source" label="Product source" :value="$config['source'] ?? 'manual'" class="mt-3">
        <option value="manual">Manual selection</option>
        <option value="flash_sale">Flash sale</option>
    </x-form.select>

    <x-form.input name="flash_sale_id" label="Flash Sale ID" :value="$config['flash_sale_id'] ?? ''" class="mt-3"
        helpText="Used when source is Flash sale." />

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="background_color" type="color" label="Background colour" :value="$config['background_color'] ?? '#ffffff'" />
        <x-form.input name="text_color" type="color" label="Text colour" :value="$config['text_color'] ?? '#111827'" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.toggle name="show_countdown_timer" label="Show countdown" :value="$config['show_countdown_timer'] ?? true" />
        <x-form.toggle name="show_discount_badge" label="Discount badge" :value="$config['show_discount_badge'] ?? true" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>