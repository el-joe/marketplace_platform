@extends('layouts.marketer')

@section('title', __('marketer.media_kit.title'))
@section('page-title', __('marketer.media_kit.title'))

@section('content')

    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-800">{{ __('marketer.media_kit.title') }}</h2>
        <p class="text-sm text-gray-500">{{ __('marketer.media_kit.subtitle') }}</p>
        @if($mediaKit->last_updated_at)
            <p class="text-xs text-gray-400 mt-1">{{ __('marketer.media_kit.last_updated', ['date' => $mediaKit->last_updated_at->format('d M Y, H:i')]) }}</p>
        @endif
    </div>

    <form method="POST" action="{{ route('marketer.media-kit.update') }}"
        x-data="{
            portfolioUrls: {{ Js::from($mediaKit->portfolio_urls ?: ['']) }},
            pastBrands: {{ Js::from($mediaKit->past_brands ?: ['']) }},
        }">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.media_kit.profile') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.headline') }}</label>
                    <input type="text" name="headline" maxlength="200" value="{{ old('headline', $mediaKit->headline) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.audience_age_range') }}</label>
                    <input type="text" name="audience_age_range" maxlength="50" placeholder="18-24"
                        value="{{ old('audience_age_range', $mediaKit->audience_age_range) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.audience_gender_split') }}</label>
                    <input type="text" name="audience_gender_split" maxlength="100" placeholder="60% F / 40% M"
                        value="{{ old('audience_gender_split', $mediaKit->audience_gender_split) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.primary_audience_country') }}</label>
                    <input type="text" name="primary_audience_country" maxlength="100"
                        value="{{ old('primary_audience_country', $mediaKit->primary_audience_country) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="hidden" name="is_visible_to_vendors" value="0">
                    <input type="checkbox" id="is_visible_to_vendors" name="is_visible_to_vendors" value="1"
                        {{ old('is_visible_to_vendors', $mediaKit->is_visible_to_vendors) ? 'checked' : '' }}
                        class="rounded border-gray-300">
                    <label for="is_visible_to_vendors" class="text-sm text-gray-700">{{ __('marketer.media_kit.visible_to_vendors') }}</label>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.media_kit.reach') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.avg_post_reach') }}</label>
                    <input type="number" name="avg_post_reach" min="0" value="{{ old('avg_post_reach', $mediaKit->avg_post_reach) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.avg_story_views') }}</label>
                    <input type="number" name="avg_story_views" min="0" value="{{ old('avg_story_views', $mediaKit->avg_story_views) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.avg_video_views') }}</label>
                    <input type="number" name="avg_video_views" min="0" value="{{ old('avg_video_views', $mediaKit->avg_video_views) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.media_kit.rates') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.rate_per_post') }}</label>
                    <input type="number" name="rate_per_post" min="0" value="{{ old('rate_per_post', $mediaKit->rate_per_post) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.rate_per_story') }}</label>
                    <input type="number" name="rate_per_story" min="0" value="{{ old('rate_per_story', $mediaKit->rate_per_story) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.rate_per_video') }}</label>
                    <input type="number" name="rate_per_video" min="0" value="{{ old('rate_per_video', $mediaKit->rate_per_video) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">{{ __('marketer.media_kit.rate_currency') }}</label>
                    <input type="text" name="rate_currency" maxlength="3" placeholder="USD"
                        value="{{ old('rate_currency', $mediaKit->rate_currency) }}"
                        class="w-full rounded-xl border border-gray-200 text-sm p-2.5 uppercase focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.media_kit.portfolio_urls') }}</h3>

            <template x-for="(url, index) in portfolioUrls" :key="index">
                <div class="flex items-center gap-2 mb-2">
                    <input type="url" :name="'portfolio_urls[' + index + ']'" x-model="portfolioUrls[index]" maxlength="2048"
                        placeholder="https://..."
                        class="flex-1 rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                    <button type="button" @click="portfolioUrls.splice(index, 1)" class="text-gray-400 hover:text-red-500 text-sm px-2">&times;</button>
                </div>
            </template>
            <button type="button" @click="portfolioUrls.push('')" class="text-xs font-semibold text-blue-600 hover:underline">
                + {{ __('marketer.media_kit.add_url') }}
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.media_kit.past_brands') }}</h3>

            <template x-for="(brand, index) in pastBrands" :key="index">
                <div class="flex items-center gap-2 mb-2">
                    <input type="text" :name="'past_brands[' + index + ']'" x-model="pastBrands[index]" maxlength="200"
                        class="flex-1 rounded-xl border border-gray-200 text-sm p-2.5 focus:ring-2 focus:ring-blue-200 focus:border-blue-300">
                    <button type="button" @click="pastBrands.splice(index, 1)" class="text-gray-400 hover:text-red-500 text-sm px-2">&times;</button>
                </div>
            </template>
            <button type="button" @click="pastBrands.push('')" class="text-xs font-semibold text-blue-600 hover:underline">
                + {{ __('marketer.media_kit.add_brand') }}
            </button>
        </div>

        <button type="submit" class="bg-yellow-400 hover:bg-yellow-300 text-slate-900 font-bold text-sm rounded-xl px-6 py-2.5 transition-colors">
            {{ __('marketer.media_kit.save') }}
        </button>
    </form>

@endsection
