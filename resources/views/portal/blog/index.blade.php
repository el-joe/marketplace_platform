@extends('layouts.portal')

@section('title', __('portal.blog.title'))

@section('content')

{{-- ── Header ─────────────────────────────────────────────────────── --}}
<section class="bg-white border-b border-gray-100 py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900">
            {{ __('portal.blog.title') }}
        </h1>
        <p class="text-gray-500 mt-2">
            {{ $locale === 'ar'
                ? 'أخبار وتوجيهات ونصائح من منصة نون'
                : 'News, guides and tips from the Noon platform' }}
        </p>

        <form method="GET" action="{{ route('portal.blog.index') }}" class="mt-6 flex gap-3 max-w-xl">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('portal.blog.search_blog') }}"
                   class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm
                          focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-gray-900">
            <button type="submit" class="btn btn-primary btn-sm">
                {{ __('portal.blog.search') }}
            </button>
        </form>
    </div>
</section>

{{-- ── Featured Post ───────────────────────────────────────────────── --}}
@if($featuredPost)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <a href="{{ route('portal.blog.show', $featuredPost->slug) }}"
           class="block rounded-2xl overflow-hidden bg-white border border-gray-200
                  hover:shadow-lg transition-shadow group">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                @if($featuredPost->featured_image_path)
                    <div class="aspect-video lg:aspect-auto overflow-hidden">
                        <img src="{{ Storage::url($featuredPost->featured_image_path) }}"
                             alt="{{ $featuredPost->{'featured_image_alt_' . $locale} }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                @endif
                <div class="p-8 flex flex-col justify-center">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold
                                 rounded-full px-3 py-1 w-fit mb-4"
                          style="background-color: {{ $featuredPost->category->color_hex ?? '#EFF6FF' }}20;
                                 color: {{ $featuredPost->category->color_hex ?? '#3B82F6' }}">
                        {{ $featuredPost->category->{'name_' . $locale} }}
                    </span>
                    <h2 class="text-2xl font-bold text-gray-900 group-hover:text-primary-600
                               transition-colors leading-snug">
                        {{ $featuredPost->{'title_' . $locale} }}
                    </h2>
                    <p class="text-gray-500 mt-3 leading-relaxed line-clamp-3">
                        {{ $featuredPost->{'excerpt_' . $locale} }}
                    </p>
                    <div class="flex items-center gap-4 mt-5 text-xs text-gray-400">
                        <span>{{ $featuredPost->author->name }}</span>
                        <span>·</span>
                        <span>{{ $featuredPost->published_at->format('d M Y') }}</span>
                        <span>·</span>
                        <span>{{ $featuredPost->reading_time_minutes }}
                            {{ __('portal.blog.min_read') }}</span>
                    </div>
                </div>
            </div>
        </a>
    </div>
@endif

{{-- ── Category Filter Pills ───────────────────────────────────────── --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 flex flex-wrap gap-2">
    <a href="{{ route('portal.blog.index', array_filter(['search' => request('search'), 'tag' => request('tag')])) }}"
       class="rounded-full px-4 py-1.5 text-sm font-medium border transition-colors
              {{ !request('category')
                  ? 'bg-primary-600 text-white border-primary-600'
                  : 'bg-white text-gray-600 border-gray-200 hover:border-primary-400' }}">
        {{ __('portal.blog.all') }}
    </a>

    @foreach($categories as $cat)
        <a href="{{ route('portal.blog.index', array_filter(['category' => $cat->slug, 'search' => request('search')])) }}"
           class="rounded-full px-4 py-1.5 text-sm font-medium border transition-colors
                  {{ request('category') === $cat->slug
                      ? 'text-white border-transparent'
                      : 'bg-white text-gray-600 border-gray-200 hover:border-primary-400' }}"
           @if(request('category') === $cat->slug && $cat->color_hex)
               style="background-color: {{ $cat->color_hex }}; border-color: {{ $cat->color_hex }}"
           @endif>
            {{ $cat->{'name_' . $locale} }}
            <span class="ml-1 text-xs opacity-70">({{ $cat->posts_count }})</span>
        </a>
    @endforeach
</div>

{{-- ── Posts Grid ──────────────────────────────────────────────────── --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 mb-16">
    @if($posts->isEmpty())
        <div class="py-20 text-center text-gray-400">
            <p class="text-lg">{{ __('portal.blog.no_posts') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                @include('portal.blog._post-card', ['post' => $post, 'locale' => $locale])
            @endforeach
        </div>
        <div class="mt-10">{{ $posts->links() }}</div>
    @endif
</div>

@endsection
