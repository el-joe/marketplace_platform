@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/rich-editor.js', 'resources/js/components/file-upload.js'])
@endpush

@section('title', 'New Blog Post')

@section('content')
<div class="p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">New Blog Post</h1>
            <p class="text-sm text-gray-500 mt-0.5">Fill in the content below and publish when ready.</p>
        </div>
        <a href="{{ route('admin.blog.posts.index') }}" class="btn btn-secondary btn-sm">← Back to Posts</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <form id="post-form" method="POST" action="{{ route('admin.blog.posts.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="action" id="form-action" value="draft">

        <div class="flex gap-6 items-start">

            {{-- ────────────── LEFT COLUMN (70%) ────────────── --}}
            <div class="flex-1 min-w-0 space-y-5">

                {{-- Content section --}}
                <x-card title="Post Content">
                    <div class="p-6 space-y-5">

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Title (English) <span class="text-red-500">*</span></label>
                                <input type="text" name="title_en" id="title-en" required maxlength="255"
                                       value="{{ old('title_en') }}"
                                       class="form-input w-full @error('title_en') is-invalid @enderror"
                                       placeholder="Your article title…">
                                @error('title_en') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">العنوان (عربي) <span class="text-red-500">*</span></label>
                                <input type="text" name="title_ar" id="title-ar" required maxlength="255" dir="rtl"
                                       value="{{ old('title_ar') }}"
                                       class="form-input w-full @error('title_ar') is-invalid @enderror"
                                       placeholder="عنوان المقال…">
                                @error('title_ar') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Slug</label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-400 whitespace-nowrap">portal.com/blog/</span>
                                <input type="text" name="slug" id="slug-input" maxlength="255"
                                       value="{{ old('slug') }}"
                                       class="form-input flex-1 text-sm font-mono @error('slug') is-invalid @enderror"
                                       placeholder="auto-generated-from-title">
                            </div>
                            @error('slug') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="form-label">Excerpt (EN)</label>
                                    <span class="text-xs text-gray-400" data-char-counter="excerpt_en" data-max="300">0 / 300</span>
                                </div>
                                <textarea name="excerpt_en" id="excerpt_en" rows="3" maxlength="300"
                                          class="form-textarea w-full @error('excerpt_en') is-invalid @enderror"
                                          placeholder="Brief summary for listings and meta…">{{ old('excerpt_en') }}</textarea>
                                @error('excerpt_en') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="form-label">مقتطف (AR)</label>
                                    <span class="text-xs text-gray-400" data-char-counter="excerpt_ar" data-max="300">0 / 300</span>
                                </div>
                                <textarea name="excerpt_ar" id="excerpt_ar" rows="3" maxlength="300" dir="rtl"
                                          class="form-textarea w-full @error('excerpt_ar') is-invalid @enderror"
                                          placeholder="ملخص مختصر…">{{ old('excerpt_ar') }}</textarea>
                                @error('excerpt_ar') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        {{-- EN / AR body tabs --}}
                        <div>
                            <div class="flex border-b border-gray-200 mb-4">
                                <button type="button" class="lang-tab px-4 py-2 text-sm font-medium border-b-2 border-primary-500 text-primary-600 -mb-px" data-lang="en">
                                    English
                                </button>
                                <button type="button" class="lang-tab px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 -mb-px" data-lang="ar">
                                    العربية
                                </button>
                            </div>

                            <div id="body-en-section">
                                <x-form.rich-editor
                                    name="body_en"
                                    label="Body (English)"
                                    :required="true"
                                    profile="full"
                                    :minHeight="350"
                                    :value="old('body_en', '')"
                                />
                                @error('body_en') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <div id="body-ar-section" class="hidden">
                                <x-form.rich-editor
                                    name="body_ar"
                                    label="محتوى المقال (عربي)"
                                    :required="true"
                                    profile="full"
                                    :minHeight="350"
                                    :value="old('body_ar', '')"
                                />
                                @error('body_ar') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Tags</label>
                            <input type="text" name="tags" id="tags-input"
                                   value="{{ old('tags') }}"
                                   class="form-input w-full text-sm"
                                   placeholder="travel, tips, budget (comma-separated)">
                            <p class="text-xs text-gray-400 mt-0.5">Comma-separated. Will be stored as JSON array.</p>
                        </div>
                    </div>
                </x-card>

                {{-- Bottom action buttons --}}
                <div class="flex items-center gap-3">
                    <button type="button" class="btn btn-secondary" onclick="submitForm('draft')">Save Draft</button>
                    <button type="button" class="btn bg-emerald-600 text-white hover:bg-emerald-700" onclick="submitForm('publish')">Publish Now</button>
                </div>

            </div>

            {{-- ────────────── RIGHT COLUMN (30%) ────────────── --}}
            <div class="w-80 flex-shrink-0 space-y-4">

                {{-- Publish Settings --}}
                <x-card title="Publish Settings">
                    <div class="p-4 space-y-4">
                        <div class="flex flex-col gap-2">
                            <button type="button" class="btn btn-secondary w-full text-sm" onclick="submitForm('draft')">
                                Save Draft
                            </button>
                            <button type="button" id="btn-schedule" class="btn btn-secondary w-full text-sm text-blue-600 border-blue-300">
                                Schedule…
                            </button>
                            <button type="button" class="btn bg-emerald-600 text-white hover:bg-emerald-700 w-full text-sm" onclick="submitForm('publish')">
                                Publish Now
                            </button>
                        </div>

                        <div id="schedule-panel" class="hidden space-y-2 pt-2 border-t border-gray-100">
                            <label class="form-label text-xs">Scheduled For</label>
                            <input type="datetime-local" name="scheduled_for" id="scheduled-for"
                                   value="{{ old('scheduled_for') }}"
                                   class="form-input w-full text-sm">
                            <button type="button" class="btn btn-primary w-full text-sm" onclick="submitForm('schedule')">
                                Set Schedule
                            </button>
                        </div>

                        <div class="pt-2 border-t border-gray-100">
                            <div class="text-xs text-gray-500">Reading time: <span id="reading-time-display" class="font-medium text-gray-700">~1 min read</span></div>
                        </div>
                    </div>
                </x-card>

                {{-- Organization --}}
                <x-card title="Organization">
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="form-label text-xs">Category <span class="text-red-500">*</span></label>
                            <select name="blog_category_id" class="form-input w-full text-sm @error('blog_category_id') is-invalid @enderror">
                                <option value="">— Select category —</option>
                                @foreach($categories as $parent)
                                    <optgroup label="{{ $parent->name_en }}">
                                        <option value="{{ $parent->id }}" {{ old('blog_category_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name_en }}
                                        </option>
                                        @foreach($parent->children as $child)
                                            <option value="{{ $child->id }}" {{ old('blog_category_id') == $child->id ? 'selected' : '' }}>
                                                ↳ {{ $child->name_en }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('blog_category_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label text-xs">Country</label>
                            <select name="country_id" class="form-input w-full text-sm">
                                <option value="">All Countries</option>
                                @foreach($countries as $c)
                                    <option value="{{ $c->id }}" {{ old('country_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label text-xs">Author</label>
                            <select name="author_admin_id" class="form-input w-full text-sm">
                                @foreach($authors as $a)
                                    <option value="{{ $a->id }}" {{ old('author_admin_id', auth('admin')->id()) == $a->id ? 'selected' : '' }}>
                                        {{ $a->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="allow_comments" value="1"
                                   class="rounded text-primary-600"
                                   {{ old('allow_comments', '1') ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">Allow Comments</span>
                        </label>
                    </div>
                </x-card>

                {{-- Featured Image --}}
                <x-card title="Featured Image">
                    <div class="p-4 space-y-4">
                        <x-form.file-upload
                            name="featured_image"
                            label="Featured Image"
                            accept="image/*"
                            :maxSizeMb="5"
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="form-label text-xs">Alt Text (EN)</label>
                                <input type="text" name="featured_image_alt_en" value="{{ old('featured_image_alt_en') }}"
                                       class="form-input w-full text-sm" placeholder="Image description…">
                            </div>
                            <div>
                                <label class="form-label text-xs">النص البديل (AR)</label>
                                <input type="text" name="featured_image_alt_ar" value="{{ old('featured_image_alt_ar') }}" dir="rtl"
                                       class="form-input w-full text-sm">
                            </div>
                        </div>
                        <div class="border-t border-gray-100 pt-3">
                            <x-form.file-upload
                                name="og_image"
                                label="Social Share Image (optional)"
                                accept="image/*"
                                :maxSizeMb="5"
                            />
                            <p class="text-xs text-gray-400 mt-1">Uses Featured Image if not set.</p>
                        </div>
                    </div>
                </x-card>

                {{-- SEO --}}
                <x-card title="SEO">
                    <div class="p-4 space-y-4">
                        <div>
                            <div class="flex justify-between mb-1">
                                <label class="form-label text-xs">SEO Title (EN)</label>
                                <span class="text-xs text-gray-400" data-char-counter="seo_title_en" data-max="60">0 / 60</span>
                            </div>
                            <input type="text" name="seo_title_en" id="seo-title-en" maxlength="200"
                                   value="{{ old('seo_title_en') }}"
                                   class="form-input w-full text-sm">
                        </div>
                        <div>
                            <div class="flex justify-between mb-1">
                                <label class="form-label text-xs">SEO Title (AR)</label>
                                <span class="text-xs text-gray-400" data-char-counter="seo_title_ar" data-max="60">0 / 60</span>
                            </div>
                            <input type="text" name="seo_title_ar" maxlength="200" dir="rtl"
                                   value="{{ old('seo_title_ar') }}"
                                   class="form-input w-full text-sm">
                        </div>
                        <div>
                            <div class="flex justify-between mb-1">
                                <label class="form-label text-xs">SEO Description (EN)</label>
                                <span class="text-xs text-gray-400" data-char-counter="seo_description_en" data-max="160">0 / 160</span>
                            </div>
                            <textarea name="seo_description_en" id="seo-desc-en" rows="2" maxlength="500"
                                      class="form-textarea w-full text-sm">{{ old('seo_description_en') }}</textarea>
                        </div>
                        <div>
                            <div class="flex justify-between mb-1">
                                <label class="form-label text-xs">SEO Description (AR)</label>
                                <span class="text-xs text-gray-400" data-char-counter="seo_description_ar" data-max="160">0 / 160</span>
                            </div>
                            <textarea name="seo_description_ar" rows="2" maxlength="500" dir="rtl"
                                      class="form-textarea w-full text-sm">{{ old('seo_description_ar') }}</textarea>
                        </div>

                        {{-- Google snippet preview --}}
                        <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 space-y-0.5">
                            <p class="text-xs text-gray-400 mb-2 font-medium uppercase">Preview</p>
                            <div id="seo-preview-title" class="text-blue-700 text-sm font-medium leading-snug truncate">Your SEO title will appear here</div>
                            <div class="text-green-700 text-xs truncate">portal.com/blog/<span id="seo-preview-slug">slug</span></div>
                            <div id="seo-preview-desc" class="text-gray-500 text-xs leading-relaxed line-clamp-2">Your SEO description will appear here as a snippet in search results.</div>
                        </div>
                    </div>
                </x-card>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    // ── Slug auto-generate ────────────────────────────────────────────────────
    const titleEn  = document.getElementById('title-en');
    const slugInput = document.getElementById('slug-input');
    let slugManual  = false;

    slugInput.addEventListener('input', () => { slugManual = true; });
    titleEn.addEventListener('input', () => {
        if (!slugManual) {
            slugInput.value = titleEn.value.toLowerCase().trim()
                .replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-').replace(/^-+|-+$/g, '');
            document.getElementById('seo-preview-slug').textContent = slugInput.value || 'slug';
        }
    });

    // ── SEO preview ───────────────────────────────────────────────────────────
    const seoTitle = document.getElementById('seo-title-en');
    const seoDesc  = document.getElementById('seo-desc-en');
    seoTitle.addEventListener('input', () => {
        document.getElementById('seo-preview-title').textContent = seoTitle.value || 'Your SEO title will appear here';
    });
    seoDesc.addEventListener('input', () => {
        document.getElementById('seo-preview-desc').textContent = seoDesc.value || 'Your SEO description will appear here as a snippet in search results.';
    });
    slugInput.addEventListener('input', () => {
        document.getElementById('seo-preview-slug').textContent = slugInput.value || 'slug';
    });

    // ── Lang tabs ─────────────────────────────────────────────────────────────
    document.querySelectorAll('.lang-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            const lang = tab.dataset.lang;
            document.querySelectorAll('.lang-tab').forEach(t => {
                t.classList.toggle('border-primary-500', t === tab);
                t.classList.toggle('text-primary-600', t === tab);
                t.classList.toggle('border-transparent', t !== tab);
                t.classList.toggle('text-gray-500', t !== tab);
            });
            document.getElementById('body-en-section').classList.toggle('hidden', lang !== 'en');
            document.getElementById('body-ar-section').classList.toggle('hidden', lang !== 'ar');
        });
    });

    // ── Schedule panel toggle ─────────────────────────────────────────────────
    document.getElementById('btn-schedule').addEventListener('click', () => {
        document.getElementById('schedule-panel').classList.toggle('hidden');
    });

    // ── Reading time estimate ─────────────────────────────────────────────────
    function estimateReadingTime() {
        const bodyEl = document.getElementById('body_en');
        if (!bodyEl) return;
        const text = bodyEl.value.replace(/<[^>]+>/g, ' ');
        const words = text.trim().split(/\s+/).filter(Boolean).length;
        const mins = Math.max(1, Math.ceil(words / 200));
        document.getElementById('reading-time-display').textContent = `~${mins} min read`;
    }
    const bodyEnEl = document.getElementById('body_en');
    if (bodyEnEl) {
        bodyEnEl.addEventListener('input', () => { clearTimeout(bodyEnEl._rt); bodyEnEl._rt = setTimeout(estimateReadingTime, 600); });
    }

    // ── Char counters ─────────────────────────────────────────────────────────
    document.querySelectorAll('[data-char-counter]').forEach(counter => {
        const fieldName = counter.dataset.charCounter;
        const max = parseInt(counter.dataset.max);
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        const update = () => { counter.textContent = `${field.value.length} / ${max}`; };
        field.addEventListener('input', update);
        update();
    });

    // ── Form submit helper ────────────────────────────────────────────────────
    window.submitForm = function (action) {
        document.getElementById('form-action').value = action;
        document.getElementById('post-form').submit();
    };
})();
</script>
@endpush
@endsection
