@extends('layouts.portal')

@section('title', $post->{'seo_title_' . $locale} ?? $post->{'title_' . $locale})
@section('meta_description', $post->{'seo_description_' . $locale} ?? $post->{'excerpt_' . $locale})

@push('head')
@if($post->og_image_path || $post->featured_image_path)
    <meta property="og:image" content="{{ Storage::url($post->og_image_path ?? $post->featured_image_path) }}">
@endif
<meta property="og:title" content="{{ $post->{'seo_title_' . $locale} ?? $post->{'title_' . $locale} }}">
<meta property="og:description" content="{{ $post->{'seo_description_' . $locale} ?? $post->{'excerpt_' . $locale} }}">
<meta property="og:type" content="article">
@if($post->published_at)
    <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
@endif
@endpush

@section('content')

<div class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ── Breadcrumb ───────────────────────────────────────────── --}}
        <nav class="flex items-center gap-2 text-xs text-gray-400 mb-6 flex-wrap" aria-label="Breadcrumb">
            <a href="{{ route('portal.home') }}" class="hover:text-gray-600 transition-colors">
                {{ __('portal.nav.home') }}
            </a>
            <span>/</span>
            <a href="{{ route('portal.blog.index') }}" class="hover:text-gray-600 transition-colors">
                {{ __('portal.blog.title') }}
            </a>
            @if($post->category)
                <span>/</span>
                <a href="{{ route('portal.blog.index', ['category' => $post->category->slug]) }}"
                   class="hover:text-gray-600 transition-colors">
                    {{ $post->category->{'name_' . $locale} }}
                </a>
            @endif
            <span>/</span>
            <span class="text-gray-600 line-clamp-1">{{ $post->{'title_' . $locale} }}</span>
        </nav>

        <article class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

            {{-- ── Article Header ───────────────────────────────────── --}}
            <div class="p-8 pb-6">
                @if($post->category)
                    <a href="{{ route('portal.blog.index', ['category' => $post->category->slug]) }}"
                       class="inline-flex items-center text-xs font-semibold rounded-full px-3 py-1 mb-5"
                       style="background-color: {{ $post->category->color_hex ?? '#EFF6FF' }}20;
                              color: {{ $post->category->color_hex ?? '#3B82F6' }}">
                        {{ $post->category->{'name_' . $locale} }}
                    </a>
                @endif

                <h1 class="text-3xl font-bold text-gray-900 leading-snug"
                    dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
                    {{ $post->{'title_' . $locale} }}
                </h1>

                <p class="text-gray-500 mt-3 text-lg leading-relaxed"
                   dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
                    {{ $post->{'excerpt_' . $locale} }}
                </p>

                <div class="flex items-center gap-4 mt-5 text-sm text-gray-400 flex-wrap">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center
                                    text-primary-600 text-xs font-bold">
                            {{ mb_substr($post->author->name, 0, 1) }}
                        </div>
                        <span class="text-gray-600 font-medium">{{ $post->author->name }}</span>
                    </div>
                    <span>·</span>
                    <span>{{ $post->published_at->format('d M Y') }}</span>
                    <span>·</span>
                    <span>{{ $post->reading_time_minutes }} {{ __('portal.blog.min_read') }}</span>
                    @if($post->views_count)
                        <span>·</span>
                        <span>{{ number_format($post->views_count) }} {{ $locale === 'ar' ? 'مشاهدة' : 'views' }}</span>
                    @endif
                </div>
            </div>

            {{-- ── Featured Image ───────────────────────────────────── --}}
            @if($post->featured_image_path)
                <div class="px-8 pb-6">
                    <img src="{{ Storage::url($post->featured_image_path) }}"
                         alt="{{ $post->{'featured_image_alt_' . $locale} }}"
                         class="w-full rounded-xl object-cover max-h-[480px]">
                </div>
            @endif

            {{-- ── Article Body ─────────────────────────────────────── --}}
            <div class="px-8 pb-8">
                <div class="blog-prose {{ $locale === 'ar' ? 'text-right' : '' }}"
                     dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
                    {!! $post->{'body_' . $locale} !!}
                </div>
            </div>

            {{-- ── Tags ─────────────────────────────────────────────── --}}
            @if($post->tags && count($post->tags) > 0)
                <div class="px-8 pb-8 pt-0">
                    <div class="flex flex-wrap gap-2 pt-8 border-t border-gray-100">
                        @foreach($post->tags as $tag)
                            <a href="{{ route('portal.blog.index', ['tag' => $tag]) }}"
                               class="rounded-full px-3 py-1 text-xs font-medium bg-gray-100
                                      text-gray-600 hover:bg-primary-50 hover:text-primary-700 transition-colors">
                                #{{ $tag }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── Author Card ──────────────────────────────────────── --}}
            <div class="px-8 pb-8">
                <div class="p-6 bg-gray-50 rounded-2xl flex items-start gap-4 border border-gray-200">
                    <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center
                                justify-center text-primary-700 font-bold text-lg flex-shrink-0">
                        {{ mb_substr($post->author->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-medium mb-0.5">
                            {{ __('portal.blog.written_by') }}
                        </p>
                        <p class="font-semibold text-gray-800">{{ $post->author->name }}</p>
                    </div>
                </div>
            </div>

        </article>

        {{-- ── Related Posts ────────────────────────────────────────── --}}
        @if($relatedPosts->isNotEmpty())
            <div class="mt-12 mb-16">
                <h2 class="text-xl font-bold text-gray-900 mb-6">
                    {{ __('portal.blog.related_posts') }}
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    @foreach($relatedPosts as $related)
                        @include('portal.blog._post-card', ['post' => $related, 'locale' => $locale])
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>

@endsection

@push('scripts')
<script>
    fetch('{{ route('portal.blog.posts.increment-views', $post->id) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    }).catch(() => {});
</script>
@endpush

@push('head')
<style>
.blog-prose { color: #374151; line-height: 1.8; font-size: 1rem; }
.blog-prose h2 { font-size: 1.5rem; font-weight: 700; color: #111827; margin: 2rem 0 1rem; }
.blog-prose h3 { font-size: 1.25rem; font-weight: 600; color: #111827; margin: 1.5rem 0 0.75rem; }
.blog-prose p { margin: 0 0 1.25rem; }
.blog-prose ul, .blog-prose ol { padding-inline-start: 1.5rem; margin: 0 0 1.25rem; }
.blog-prose li { margin-bottom: 0.4rem; }
.blog-prose blockquote { border-inline-start: 4px solid #d1d5db; padding-inline-start: 1rem;
    color: #6b7280; font-style: italic; margin: 1.5rem 0; }
.blog-prose a { color: #2563eb; text-decoration: underline; }
.blog-prose a:hover { color: #1d4ed8; }
.blog-prose img { border-radius: 0.75rem; max-width: 100%; height: auto; margin: 1.5rem 0; }
.blog-prose strong { font-weight: 600; color: #111827; }
.blog-prose code { background: #f3f4f6; padding: 0.15rem 0.4rem; border-radius: 0.25rem;
    font-size: 0.875em; }
.blog-prose pre { background: #1f2937; color: #f9fafb; padding: 1.25rem; border-radius: 0.75rem;
    overflow-x: auto; margin: 1.5rem 0; }
.blog-prose pre code { background: none; padding: 0; color: inherit; }
.blog-prose hr { border: none; border-top: 1px solid #e5e7eb; margin: 2rem 0; }
</style>
@endpush
