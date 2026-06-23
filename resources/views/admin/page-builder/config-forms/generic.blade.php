{{-- Generic / fallback config form (no specific partial defined) --}}
<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
        No specific config UI exists for <strong>{{ $blockType->label_en }}</strong> yet.
        Edit raw JSON below.
    </div>

    <x-form.textarea name="__raw_json"
                     label="Raw config (JSON)"
                     rows="10"
                     :value="json_encode($config, JSON_PRETTY_PRINT)" />

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>
