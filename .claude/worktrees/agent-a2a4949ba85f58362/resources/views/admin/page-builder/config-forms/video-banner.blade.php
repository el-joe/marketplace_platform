@php /** @var \App\Models\PageBlock|null $block */ @endphp

<form data-config-form data-block-id="{{ $block?->id }}">
    @csrf

    <x-form.input name="video_url" label="Video URL" :value="$config['video_url'] ?? ''"
        placeholder="https://www.youtube.com/watch?v=… or direct .mp4 URL"
        helpText="Supports YouTube, Vimeo, or a direct MP4 link." />

    <x-form.input name="thumbnail_url" label="Thumbnail image URL" :value="$config['thumbnail_url'] ?? ''" class="mt-3"
        placeholder="https://…/thumbnail.jpg" helpText="Displayed before the video loads." />

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="title_en" label="Overlay title (EN)" :value="$config['title_en'] ?? ''" />
        <x-form.input name="title_ar" label="Overlay title (AR)" :value="$config['title_ar'] ?? ''" />
    </div>

    <x-form.input name="cta_url" label="CTA URL" :value="$config['cta_url'] ?? ''" class="mt-3"
        placeholder="/products or https://…" />

    <div class="grid grid-cols-2 gap-3 mt-3">
        <x-form.input name="cta_label_en" label="CTA label (EN)" :value="$config['cta_label_en'] ?? ''" />
        <x-form.input name="cta_label_ar" label="CTA label (AR)" :value="$config['cta_label_ar'] ?? ''" />
    </div>

    <x-form.select name="aspect_ratio" label="Aspect ratio" :value="$config['aspect_ratio'] ?? '16:9'" class="mt-3">
        <option value="16:9">16 : 9 (Widescreen)</option>
        <option value="4:3">4 : 3</option>
        <option value="1:1">1 : 1 (Square)</option>
        <option value="21:9">21 : 9 (Cinematic)</option>
    </x-form.select>

    <div class="grid grid-cols-3 gap-3 mt-3">
        <x-form.toggle name="autoplay" label="Autoplay" :value="$config['autoplay'] ?? false" />
        <x-form.toggle name="muted" label="Muted" :value="$config['muted'] ?? true" />
        <x-form.toggle name="loop" label="Loop" :value="$config['loop'] ?? false" />
    </div>

    @include('admin.page-builder.config-forms.partials.visibility', ['block' => $block])
</form>