@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <x-form.select name="style" label="Style" :value="$config['style'] ?? 'solid'">
            <option value="solid">Solid line</option>
            <option value="dashed">Dashed</option>
            <option value="dotted">Dotted</option>
            <option value="spacer">Spacer only</option>
        </x-form.select>
        <x-form.input name="color" type="color" label="Color" :value="$config['color'] ?? '#e5e7eb'" />
    </div>

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="margin_top"    type="number" label="Margin top (px)"    :value="$config['margin_top']    ?? 16" min="0" />
        <x-form.input name="margin_bottom" type="number" label="Margin bottom (px)" :value="$config['margin_bottom'] ?? 16" min="0" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
