@php
    $hasError = $errors->has($name);
    $toolbarJson = json_encode($toolbarConfig, JSON_THROW_ON_ERROR);
    $safeValue = old($name, $value ?? '');
@endphp

<div class="space-y-1">
    @if($label)
        <label class="block text-sm font-medium text-gray-700">
            {{ $label }}
            @if($required)
                <span class="text-danger-500 ml-0.5" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <div class="rounded-lg border {{ $hasError ? 'border-danger-500' : 'border-gray-300' }} overflow-hidden">
        {{-- Toolbar --}}
        <div class="tiptap-toolbar flex flex-wrap items-center gap-0.5 px-2 py-1.5
                    border-b {{ $hasError ? 'border-danger-200' : 'border-gray-200' }}
                    bg-gray-50">

            @if($toolbarConfig['bold'] ?? false)
                <button type="button" data-tiptap-action="bold" title="Bold"
                    class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M15.6 11.8c.9-.6 1.4-1.7 1.4-2.8 0-2.2-1.8-4-4-4H6v14h7.4c2.1 0 3.6-1.5 3.6-3.6 0-1.4-.7-2.6-1.4-3.6zM9 7h3c.8 0 1.5.7 1.5 1.5S12.8 10 12 10H9V7zm3.5 9H9v-3h3.5c.8 0 1.5.7 1.5 1.5S13.3 16 12.5 16z" />
                    </svg>
                </button>
            @endif

            @if($toolbarConfig['italic'] ?? false)
                <button type="button" data-tiptap-action="italic" title="Italic"
                    class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4z" />
                    </svg>
                </button>
            @endif

            @if($toolbarConfig['heading'] ?? false)
                <button type="button" data-tiptap-action="heading" data-level="2" title="Heading 2"
                    class="tiptap-btn px-2 py-1 rounded hover:bg-gray-200 text-gray-600 text-xs font-bold">H2</button>
                <button type="button" data-tiptap-action="heading" data-level="3" title="Heading 3"
                    class="tiptap-btn px-2 py-1 rounded hover:bg-gray-200 text-gray-600 text-xs font-bold">H3</button>
            @endif

            @if($toolbarConfig['bulletList'] ?? false)
                <button type="button" data-tiptap-action="bulletList" title="Bullet List"
                    class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            @endif

            @if($toolbarConfig['orderedList'] ?? false)
                <button type="button" data-tiptap-action="orderedList" title="Numbered List"
                    class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5h11M9 12h11M9 19h11M5 5v.01M5 12v.01M5 19v.01" />
                    </svg>
                </button>
            @endif

            @if($toolbarConfig['blockquote'] ?? false)
                <button type="button" data-tiptap-action="blockquote" title="Blockquote"
                    class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600 font-serif text-base">"</button>
            @endif

            @if($toolbarConfig['link'] ?? false)
                <button type="button" data-tiptap-action="link" title="Insert Link"
                    class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 0 0-5.656 0l-4 4a4 4 0 1 0 5.656 5.656l1.102-1.101m-.758-4.899a4 4 0 0 0 5.656 0l4-4a4 4 0 0 0-5.656-5.656l-1.1 1.1" />
                    </svg>
                </button>
            @endif

            @if($toolbarConfig['image'] ?? false)
                <button type="button" data-tiptap-action="image" title="Insert Image"
                    class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </button>
            @endif

            <div class="w-px h-5 bg-gray-300 mx-1"></div>

            <button type="button" data-tiptap-action="undo" title="Undo"
                class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                </svg>
            </button>
            <button type="button" data-tiptap-action="redo" title="Redo"
                class="tiptap-btn p-1.5 rounded hover:bg-gray-200 text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="m15 15 6-6m0 0-6-6m6 6H9a6 6 0 0 0 0 12h3" />
                </svg>
            </button>
        </div>

        {{-- Editor area --}}
        <div id="{{ $name }}-editor" data-rich-editor="{{ $name }}" data-profile="{{ $profile }}"
            data-toolbar='{{ $toolbarJson }}' @if($uploadUrl) data-upload-url="{{ $uploadUrl }}" @endif
            style="min-height: {{ $minHeight }}px;"
            class="prose prose-sm max-w-none px-3 py-2 focus:outline-none bg-white"></div>
    </div>

    {{-- Hidden input carrying the HTML value --}}
    <input type="hidden" name="{{ $name }}" id="{{ $name }}-hidden" value="{{ old($name, $value) }}">

    @if($helpText && !$hasError)
        <p class="text-xs text-gray-500">{{ $helpText }}</p>
    @endif

    @error($name)
        <p class="flex items-center gap-1 text-xs text-danger-600">
            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            {{ $message }}
        </p>
    @enderror
</div>