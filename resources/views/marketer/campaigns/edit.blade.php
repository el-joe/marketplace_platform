@extends('layouts.marketer')

@section('title', __('marketer.campaigns.edit_and_resubmit'))
@section('page-title', __('marketer.campaigns.edit_and_resubmit'))

@section('content')

<div class="max-w-2xl">

    <div class="flex items-center gap-2 mb-6">
        <a href="{{ route('marketer.campaigns.show', $campaign->id) }}" class="text-sm text-gray-400 hover:text-gray-600">{{ __('marketer.campaigns.back_to_campaigns') }}</a>
    </div>

    <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6">
        <p class="text-sm text-red-700">
            {{ __('marketer.campaigns.rejected_banner', ['reason' => $campaign->rejection_reason ?? '—']) }}
        </p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <form action="{{ route('marketer.campaigns.resubmit', $campaign->id) }}" method="POST">
            @csrf

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('marketer.campaigns.name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $campaign->name) }}"
                           class="form-input w-full text-sm @error('name') border-red-400 @enderror">
                    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('marketer.campaigns.description') }}</label>
                    <textarea name="description" rows="3"
                              class="form-input w-full text-sm @error('description') border-red-400 @enderror">{{ old('description', $campaign->description) }}</textarea>
                    @error('description')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('marketer.campaigns.start_date') }}</label>
                        <input type="date" name="starts_at" value="{{ old('starts_at', $campaign->starts_at?->toDateString()) }}"
                               class="form-input w-full text-sm @error('starts_at') border-red-400 @enderror">
                        @error('starts_at')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('marketer.campaigns.end_date') }}</label>
                        <input type="date" name="ends_at" value="{{ old('ends_at', $campaign->ends_at?->toDateString()) }}"
                               class="form-input w-full text-sm @error('ends_at') border-red-400 @enderror">
                        @error('ends_at')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('marketer.campaigns.budget_cap') }}</label>
                    <input type="number" name="budget" value="{{ old('budget', $campaign->budget ? $campaign->budget / 100 : null) }}" step="0.01" min="1"
                           class="form-input w-full text-sm @error('budget') border-red-400 @enderror">
                    @error('budget')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('marketer.campaigns.attribution_model') }}</label>
                    <select name="attribution_model" class="form-input w-full text-sm">
                        @foreach(['last_click' => __('marketer.campaigns.attribution_last_click'), 'first_click' => __('marketer.campaigns.attribution_first_click'), 'linear' => __('marketer.campaigns.attribution_linear')] as $value => $label)
                            <option value="{{ $value }}" {{ old('attribution_model', $campaign->attribution_model?->value) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="whatsapp_sharing_enabled" value="1"
                               {{ old('whatsapp_sharing_enabled', $campaign->whatsapp_sharing_enabled) ? 'checked' : '' }}
                               class="rounded border-gray-300">
                        <span class="text-sm font-semibold text-gray-700">{{ __('marketer.campaigns.enable_whatsapp') }}</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-8 pt-5 border-t border-gray-100">
                <a href="{{ route('marketer.campaigns.show', $campaign->id) }}" class="btn btn-ghost btn-sm">{{ __('marketer.campaigns.cancel') }}</a>
                <button type="submit" class="btn btn-primary btn-sm px-8">{{ __('marketer.campaigns.edit_and_resubmit') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection
