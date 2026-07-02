@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <x-form.input name="height_desktop" label="Desktop height" :value="$config['height_desktop'] ?? '480px'" />
        <x-form.input name="autoplay_seconds" type="number" label="Autoplay (s)" :value="$config['autoplay_seconds'] ?? 4" min="0" />
    </div>

    <div class="grid grid-cols-3 gap-3 mt-3">
        <x-form.toggle name="show_dots"   label="Dots"   :value="$config['show_dots']   ?? true" />
        <x-form.toggle name="show_arrows" label="Arrows" :value="$config['show_arrows'] ?? true" />
        <x-form.toggle name="loop"        label="Loop"   :value="$config['loop']        ?? true" />
    </div>

    <x-form.select name="transition" label="Transition" :value="$config['transition'] ?? 'slide'" class="mt-3">
        <option value="slide">Slide</option>
        <option value="fade">Fade</option>
    </x-form.select>

    @include('admin.page-builder.config-forms.partials.slides-manager', ['block' => $block])
    @include('admin.page-builder.config-forms.partials.visibility',     ['block' => $block])
</form>
