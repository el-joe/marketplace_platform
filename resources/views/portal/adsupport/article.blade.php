@extends('layouts.adsupport')

@php
    $collection = $article['collection_id'] ? ($collections[$article['collection_id']] ?? null) : null;
    $subcollection = ($collection && $article['subcollection_id'])
        ? ($collection['subcollections'][$article['subcollection_id']] ?? null)
        : null;
@endphp

@section('title', $article['title'] . ' | Advertise smarter, grow faster')
@section('description', '')

@section('header')
    @include('portal.partials.adsupport-header', ['variant' => 'lite', 'country' => $country])
@endsection

@section('content')
    <div class="justify-between flex">
        <div class="relative z-3 w-full lg:max-w-[640px]">
            <div class="flex pb-6 max-md:pb-2 lg:max-w-[640px]">
                <nav class="pb-4 text-base" aria-label="Breadcrumb">
                    <ol class="m-0 flex list-none flex-wrap items-baseline gap-2 p-0">
                        <li class="flex items-center gap-2">
                            <a href="{{ route('portal.adsupport.index', $country) }}" class="text-black no-underline hover:text-[#737373]">All Collections</a>
                            <svg width="6" height="10" viewBox="0 0 6 10" class="block h-2 w-2 fill-[#737373]" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.648862 0.898862C0.316916 1.23081 0.316916 1.769 0.648862 2.10094L3.54782 4.9999L0.648862 7.89886C0.316916 8.23081 0.316917 8.769 0.648862 9.10094C0.980808 9.43289 1.519 9.43289 1.85094 9.10094L5.35094 5.60094C5.68289 5.269 5.68289 4.73081 5.35094 4.39886L1.85094 0.898862C1.519 0.566916 0.980807 0.566916 0.648862 0.898862Z"></path></svg>
                        </li>
                        @if($collection)
                            <li class="flex items-center gap-2">
                                <a href="{{ route('portal.adsupport.collections.show', ['country' => $country, 'collection' => \App\Support\AdSupport\AdSupportCatalog::segmentFor($article['collection_id'], $collection['slug'])]) }}" class="text-black no-underline hover:text-[#737373]">{{ $collection['name'] }}</a>
                                <svg width="6" height="10" viewBox="0 0 6 10" class="block h-2 w-2 fill-[#737373]" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.648862 0.898862C0.316916 1.23081 0.316916 1.769 0.648862 2.10094L3.54782 4.9999L0.648862 7.89886C0.316916 8.23081 0.316917 8.769 0.648862 9.10094C0.980808 9.43289 1.519 9.43289 1.85094 9.10094L5.35094 5.60094C5.68289 5.269 5.68289 4.73081 5.35094 4.39886L1.85094 0.898862C1.519 0.566916 0.980807 0.566916 0.648862 0.898862Z"></path></svg>
                            </li>
                        @endif
                        @if($subcollection)
                            <li class="flex items-center gap-2">
                                <span class="text-black">{{ $subcollection['name'] }}</span>
                                <svg width="6" height="10" viewBox="0 0 6 10" class="block h-2 w-2 fill-[#737373]" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M0.648862 0.898862C0.316916 1.23081 0.316916 1.769 0.648862 2.10094L3.54782 4.9999L0.648862 7.89886C0.316916 8.23081 0.316917 8.769 0.648862 9.10094C0.980808 9.43289 1.519 9.43289 1.85094 9.10094L5.35094 5.60094C5.68289 5.269 5.68289 4.73081 5.35094 4.39886L1.85094 0.898862C1.519 0.566916 0.980807 0.566916 0.648862 0.898862Z"></path></svg>
                            </li>
                        @endif
                        <li aria-current="page" class="text-[#737373]">{{ $article['title'] }}</li>
                    </ol>
                </nav>
            </div>

            <div class="article">
                <div class="mb-10 max-lg:mb-6">
                    <div class="flex flex-col gap-4">
                        <h1 class="mb-1 text-2xl font-bold leading-10 text-black">{{ $article['title'] }}</h1>
                        <div class="-mt-0.5 text-base">
                            <span class="text-[#737373]">
                                <time datetime="{{ $article['updated_datetime'] }}" title="Updated">{{ $article['updated_label'] }}</time>
                            </span>
                        </div>
                    </div>
                </div>

                @if(!empty($article['toc']))
                    <div class="mb-7 ml-0 text-md lg:hidden" x-data="{ tocOpen: false }">
                        <div class="max-h-[calc(100vh-96px)] overflow-y-auto rounded-2xl border border-solid border-[#e6e6e6] text-black">
                            <div @click="tocOpen = !tocOpen" class="flex cursor-pointer flex-row justify-between border-b border-solid border-[#e6e6e6] px-4 py-2">
                                <div>Table of contents</div>
                                <svg class="mt-1 transition-transform" :class="tocOpen ? 'rotate-180' : ''" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M3.93353 5.93451C4.24595 5.62209 4.75248 5.62209 5.0649 5.93451L7.99922 8.86882L10.9335 5.93451C11.246 5.62209 11.7525 5.62209 12.0649 5.93451C12.3773 6.24693 12.3773 6.75346 12.0649 7.06588L8.5649 10.5659C8.25249 10.8783 7.74595 10.8783 7.43353 10.5659L3.93353 7.06588C3.62111 6.75346 3.62111 6.24693 3.93353 5.93451Z" fill="currentColor"></path></svg>
                            </div>
                            <div x-show="tocOpen" x-cloak class="my-2">
                                @foreach($article['toc'] as $item)
                                    <section class="flex border-s-2 border-solid border-transparent px-7 py-1.5">
                                        <a href="#{{ $item['id'] }}" class="w-full text-base no-underline hover:text-black">{{ $item['label'] }}</a>
                                    </section>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <div class="article_body">
                    @if(!empty($article['body_view']))
                        @include($article['body_view'], ['country' => $country])
                    @else
                        <div class="no-margin">
                            <p>Content for this article is coming soon. In the meantime, check the related articles below or the collections on the <a href="{{ route('portal.adsupport.index', $country) }}">Knowledge Hub home page</a>.</p>
                        </div>
                    @endif

                    @if(!empty($article['related_articles']))
                        <section class="my-6">
                            <hr class="my-6 sm:my-8">
                            <div class="mb-3 text-xl font-bold">Related Articles</div>
                            <section class="flex flex-col rounded-[10px] border border-solid border-[#e6e6e6] bg-white p-2 sm:p-3">
                                @foreach($article['related_articles'] as $relatedId)
                                    @php($related = $allArticles[$relatedId] ?? null)
                                    @continue(!$related)
                                    <a class="group/article flex flex-row justify-between gap-2 rounded-[10px] px-3 py-2 no-underline transition ease-linear hover:bg-black/5 sm:py-3"
                                       href="{{ route('portal.adsupport.articles.show', ['country' => $country, 'article' => \App\Support\AdSupport\AdSupportCatalog::segmentFor($relatedId, \Illuminate\Support\Str::slug($related['title']))]) }}">
                                        <span class="m-0 text-md text-black group-hover/article:text-black/70">{{ $related['title'] }}</span>
                                        <svg class="block h-4 w-4 shrink-0 text-black ltr:-rotate-90" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    </a>
                                @endforeach
                            </section>
                        </section>
                    @endif
                </div>
            </div>

            <fieldset class="m-0 mt-6 rounded-[10px] border-0 sm:mt-8" x-data="{ picked: null }">
                <legend class="float-start w-full">Did this answer your question?</legend>
                <div class="mt-4 flex gap-2">
                    <button type="button" @click="picked = 'disappointed'" :class="picked === 'disappointed' ? 'bg-black/10' : ''" class="rounded-full border border-solid border-[#e6e6e6] px-3 py-2" aria-label="Disappointed Reaction"><span aria-hidden="true">😞</span></button>
                    <button type="button" @click="picked = 'neutral'" :class="picked === 'neutral' ? 'bg-black/10' : ''" class="rounded-full border border-solid border-[#e6e6e6] px-3 py-2" aria-label="Neutral Reaction"><span aria-hidden="true">😐</span></button>
                    <button type="button" @click="picked = 'smiley'" :class="picked === 'smiley' ? 'bg-black/10' : ''" class="rounded-full border border-solid border-[#e6e6e6] px-3 py-2" aria-label="Smiley Reaction"><span aria-hidden="true">😊</span></button>
                </div>
            </fieldset>
        </div>

        @if(!empty($article['toc']))
            <div class="w-61 sticky top-8 ml-7 max-w-61 self-start max-lg:hidden mt-16">
                <div class="max-h-[calc(100vh-96px)] overflow-y-auto rounded-2xl text-black">
                    <div class="my-2">Table of contents</div>
                    <div class="my-2">
                        @foreach($article['toc'] as $item)
                            <section class="flex border-s-2 border-solid border-[#f2f2f2] px-7 py-1.5 hover:border-black/40">
                                <a href="#{{ $item['id'] }}" class="w-full text-base text-[#737373] no-underline hover:text-black">{{ $item['label'] }}</a>
                            </section>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
