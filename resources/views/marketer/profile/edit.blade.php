@extends('layouts.marketer')

@section('title', __('marketer.profile.title'))
@section('page-title', __('marketer.profile.title'))

@section('content')

<div class="max-w-2xl mx-auto">

    {{-- Alerts --}}
    <div id="alert-success" hidden
         class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-5 text-sm font-medium flex items-center justify-between">
        <span>{{ __('marketer.profile.profile_saved') }}</span>
        <button type="button" onclick="hideAlert('success')" class="text-green-500 hover:text-green-700 ml-4 leading-none text-lg">&times;</button>
    </div>
    <div id="alert-error" hidden
         class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm font-medium flex items-center justify-between">
        <span id="alert-error-msg"></span>
        <button type="button" onclick="hideAlert('error')" class="text-red-400 hover:text-red-600 ml-4 leading-none text-lg">&times;</button>
    </div>

    <form id="profile-form" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Basic Info --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.profile.basic_info') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.full_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $marketer->name) }}"
                        class="form-input text-sm py-2" required>
                </div>
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.display_name') }}</label>
                    <input type="text" name="display_name" value="{{ old('display_name', $marketer->display_name) }}"
                        class="form-input text-sm py-2" placeholder="{{ __('marketer.profile.display_name_placeholder') }}">
                </div>
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone', $marketer->phone) }}"
                        class="form-input text-sm py-2" placeholder="+966 5X XXX XXXX">
                </div>
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.niche') }}</label>
                    <input type="text" name="niche" value="{{ old('niche', $marketer->niche) }}"
                        class="form-input text-sm py-2" placeholder="{{ __('marketer.profile.niche_placeholder') }}">
                </div>
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.country') }} <span class="text-red-500">*</span></label>
                    <select name="country_id" class="form-input text-sm py-2" required>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                {{ old('country_id', $marketer->country_id) == $country->id ? 'selected' : '' }}>
                                {{ $country->name_en }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Account Status (read-only) --}}
        <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.profile.account_status') }}</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase">{{ __('marketer.profile.status') }}</p>
                    <p class="font-semibold mt-0.5 capitalize">{{ $marketer->status?->value }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">{{ __('marketer.profile.type') }}</p>
                    <p class="font-semibold mt-0.5 capitalize">{{ str_replace('_', ' ', $marketer->type?->value) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">{{ __('marketer.profile.referral_code') }}</p>
                    <p class="font-mono font-semibold mt-0.5">{{ $marketer->referral_code }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">{{ __('marketer.profile.commission_rate') }}</p>
                    <p class="font-semibold mt-0.5">{{ $marketer->commission_rate }}%</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">{{ __('marketer.profile.total_earnings') }}</p>
                    <p class="font-semibold mt-0.5">{{ number_format($marketer->total_earnings, 2) }} {{ $marketer->country?->currency_code ?? '' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">{{ __('marketer.profile.samples_used') }}</p>
                    <p class="font-semibold mt-0.5">{{ $marketer->total_samples_used }} / {{ $marketer->total_samples_allocated ?: '∞' }}</p>
                </div>
            </div>
        </div>

        {{-- Audience stats & visibility --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.profile.audience_visibility') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.followers') }}</label>
                    <input type="number" min="0" name="followers_count"
                        value="{{ old('followers_count', $marketer->followers_count) }}"
                        class="form-input text-sm py-2">
                </div>
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.engagement_rate') }}</label>
                    <input type="number" min="0" max="100" step="0.01" name="engagement_rate"
                        value="{{ old('engagement_rate', $marketer->engagement_rate) }}"
                        class="form-input text-sm py-2">
                </div>
            </div>
            <p class="text-xs text-gray-400 mb-4">{{ __('marketer.profile.followers_hint') }}</p>
            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_profile_public" value="1"
                        {{ old('is_profile_public', $marketer->is_profile_public) ? 'checked' : '' }}>
                    {{ __('marketer.profile.is_profile_public') }}
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="accept_new_campaigns" value="1"
                        {{ old('accept_new_campaigns', $marketer->accept_new_campaigns) ? 'checked' : '' }}>
                    {{ __('marketer.profile.accept_new_campaigns') }}
                </label>
            </div>
        </div>

        {{-- Public slug --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.profile.public_profile_url') }}</h3>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-400 font-mono">{{ 'https://marketer.' . env('APP_DOMAIN', 'localhost') }}/p/</span>
                <input type="text" name="boutiqaat_style_slug" id="slug-input"
                    value="{{ old('boutiqaat_style_slug', $marketer->boutiqaat_style_slug) }}"
                    class="form-input flex-1 text-sm py-2 font-mono"
                    placeholder="{{ __('marketer.profile.slug_placeholder') }}" pattern="[a-z0-9\-]+" title="{{ __('marketer.profile.slug_pattern_hint') }}">
            </div>
            <a id="slug-preview-link" href="#" target="_blank"
               class="text-xs text-blue-600 hover:underline mt-2 inline-block"
               style="{{ $marketer->boutiqaat_style_slug ? '' : 'display:none' }}">
                {{ __('marketer.profile.view_public_profile') }}
            </a>
            <a href="{{ route('marketer.store.preview') }}" target="_blank"
               class="text-xs text-blue-600 hover:underline mt-2 ml-4 inline-block">
                {{ __('marketer.profile.preview_my_store') }}
            </a>
        </div>

        {{-- Photos --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.profile.photos') }}</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.profile_photo') }}</label>
                    <img id="preview-profile-photo"
                         src="{{ $marketer->profile_photo_path ? Storage::url($marketer->profile_photo_path) : '' }}"
                         class="w-20 h-20 rounded-full object-cover border border-gray-200 mb-2"
                         style="{{ $marketer->profile_photo_path ? '' : 'display:none' }}" alt="">
                    <input type="file" name="profile_photo" accept="image/*" class="form-input text-sm py-1.5"
                           onchange="previewFile(this, 'preview-profile-photo')">
                </div>
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.banner_image') }}</label>
                    <img id="preview-profile-banner"
                         src="{{ $marketer->profile_banner_path ? Storage::url($marketer->profile_banner_path) : '' }}"
                         class="w-full h-16 rounded-lg object-cover border border-gray-200 mb-2"
                         style="{{ $marketer->profile_banner_path ? '' : 'display:none' }}" alt="">
                    <input type="file" name="profile_banner" accept="image/*" class="form-input text-sm py-1.5"
                           onchange="previewFile(this, 'preview-profile-banner')">
                </div>
            </div>
        </div>

        {{-- Bio and content --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.profile.bio_content') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="form-label">{{ __('marketer.profile.short_bio') }}</label>
                    <textarea name="bio" id="bio-input" rows="3" class="form-input text-sm py-2"
                        placeholder="{{ __('marketer.profile.bio_placeholder') }}"
                        maxlength="2000" oninput="updateCounter(this, 'bio-counter', 2000)">{{ old('bio', $marketer->bio) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1 text-right">
                        <span id="bio-counter">{{ mb_strlen(old('bio', $marketer->bio ?? '')) }}</span> / 2000
                    </p>
                </div>
                <div>
                    <label class="form-label">{{ __('marketer.profile.short_bio_ar') }}</label>
                    <textarea name="bio_ar" dir="rtl" rows="3" class="form-input text-sm py-2"
                        placeholder="{{ __('marketer.profile.bio_ar_placeholder') }}"
                        maxlength="2000">{{ old('bio_ar', $marketer->bio_ar) }}</textarea>
                </div>
                <div>
                    <label class="form-label">{{ __('marketer.profile.website_url') }}</label>
                    <input type="url" name="website_url"
                        value="{{ old('website_url', $marketer->website_url) }}"
                        class="form-input text-sm py-2" placeholder="https://example.com">
                </div>
                <div>
                    <label class="form-label">{{ __('marketer.profile.promo_content') }}</label>
                    <textarea name="promo_content" rows="4" class="form-input text-sm py-2"
                        placeholder="{{ __('marketer.profile.promo_content_placeholder') }}"
                        maxlength="3000" oninput="updateCounter(this, 'promo-counter', 3000)">{{ old('promo_content', $marketer->promo_content) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1 text-right">
                        <span id="promo-counter">{{ mb_strlen(old('promo_content', $marketer->promo_content ?? '')) }}</span> / 3000
                    </p>
                </div>
                <div>
                    <label class="form-label">{{ __('marketer.profile.featured_video') }}</label>
                    <input type="url" name="profile_video_url"
                        value="{{ old('profile_video_url', $marketer->profile_video_url) }}"
                        class="form-input text-sm py-2" placeholder="https://youtube.com/watch?v=...">
                </div>
            </div>
        </div>

        {{-- Social links --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.profile.social_links') }}</h3>
            <div class="space-y-3">
                @foreach([
                    ['social_instagram', 'Instagram', 'https://instagram.com/username'],
                    ['social_tiktok',    'TikTok',    'https://tiktok.com/@username'],
                    ['social_youtube',   'YouTube',   'https://youtube.com/@username'],
                    ['social_twitter',   'Twitter / X', 'https://x.com/username'],
                    ['social_facebook',  'Facebook',  'https://facebook.com/username'],
                    ['social_snapchat',  'Snapchat',  'https://snapchat.com/add/username'],
                ] as [$field, $label, $placeholder])
                    <div>
                        <label class="form-label text-xs">{{ $label }}</label>
                        <input type="url" name="{{ $field }}"
                            value="{{ old($field, $marketer->$field) }}"
                            class="form-input text-sm py-2" placeholder="{{ $placeholder }}">
                    </div>
                @endforeach
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.whatsapp_number') }}</label>
                    <input type="text" name="whatsapp_number"
                        value="{{ old('whatsapp_number', $marketer->whatsapp_number) }}"
                        class="form-input text-sm py-2" placeholder="+966 5X XXX XXXX">
                </div>
            </div>
        </div>

        {{-- Payout details --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">{{ __('marketer.profile.payout_details') }}</h3>
            <div class="space-y-3">
                <div>
                    <label class="form-label text-xs">{{ __('marketer.wallet.bank_name') }}</label>
                    <input type="text" name="bank_name"
                        value="{{ old('bank_name', $marketer->bank_name) }}"
                        class="form-input text-sm py-2" placeholder="e.g. Al Rajhi Bank">
                </div>
                <div>
                    <label class="form-label text-xs">IBAN</label>
                    <input type="text" name="bank_iban"
                        value="{{ old('bank_iban', $marketer->bank_iban) }}"
                        class="form-input text-sm py-2" placeholder="SA...">
                </div>
                <div>
                    <label class="form-label text-xs">{{ __('marketer.profile.bank_account_name') }}</label>
                    <input type="text" name="bank_account_name"
                        value="{{ old('bank_account_name', $marketer->bank_account_name) }}"
                        class="form-input text-sm py-2">
                </div>
            </div>
        </div>

        <button type="submit" id="submit-btn" class="btn btn-primary w-full">{{ __('marketer.profile.save_profile') }}</button>
    </form>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const BASE_URL = '{{ 'https://marketer.' . env('APP_DOMAIN', 'localhost') }}/p/';
    const UPDATE_URL = '{{ route('marketer.profile.update') }}';
    const CSRF = '{{ csrf_token() }}';

    // Slug preview link
    const slugInput = document.getElementById('slug-input');
    const slugLink  = document.getElementById('slug-preview-link');

    function updateSlugLink() {
        const val = slugInput.value.trim();
        if (val) {
            slugLink.href = BASE_URL + val;
            slugLink.style.display = '';
        } else {
            slugLink.style.display = 'none';
        }
    }
    slugInput.addEventListener('input', updateSlugLink);
    updateSlugLink();

    // File preview
    window.previewFile = function (input, imgId) {
        const file = input.files[0];
        if (!file) return;
        const img = document.getElementById(imgId);
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            img.style.display = '';
        };
        reader.readAsDataURL(file);
    };

    // Char counters
    window.updateCounter = function (textarea, counterId, max) {
        document.getElementById(counterId).textContent = textarea.value.length;
    };

    // Alert helpers
    window.hideAlert = function (type) {
        document.getElementById('alert-' + type).hidden = true;
    };

    function showSuccess() {
        document.getElementById('alert-success').hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => { document.getElementById('alert-success').hidden = true; }, 5000);
    }

    function showError(msg) {
        document.getElementById('alert-error-msg').textContent = msg;
        document.getElementById('alert-error').hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Form submit
    document.getElementById('profile-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = @json(__('marketer.profile.saving'));
        document.getElementById('alert-success').hidden = true;
        document.getElementById('alert-error').hidden = true;

        const fd = new FormData(this);
        fd.append('_method', 'PUT');

        try {
            const res = await fetch(UPDATE_URL, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (data.success) {
                showSuccess();
            } else {
                showError(data.message || @json(__('marketer.profile.validation_error')));
            }
        } catch (_) {
            showError(@json(__('marketer.profile.network_error')));
        } finally {
            btn.disabled = false;
            btn.textContent = @json(__('marketer.profile.save_profile'));
        }
    });
})();
</script>
@endpush
