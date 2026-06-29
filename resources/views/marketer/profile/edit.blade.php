@extends('layouts.marketer')

@section('title', 'Edit Profile')
@section('page-title', 'Edit Profile')

@section('content')

<div class="max-w-2xl mx-auto">

    {{-- Alerts --}}
    <div id="alert-success" hidden
         class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-5 text-sm font-medium flex items-center justify-between">
        <span>✓ Profile saved successfully.</span>
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
            <h3 class="font-bold text-gray-800 mb-4">Basic Info</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $marketer->name) }}"
                        class="form-input text-sm py-2" required>
                </div>
                <div>
                    <label class="form-label text-xs">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $marketer->phone) }}"
                        class="form-input text-sm py-2" placeholder="+966 5X XXX XXXX">
                </div>
                <div>
                    <label class="form-label text-xs">Niche</label>
                    <input type="text" name="niche" value="{{ old('niche', $marketer->niche) }}"
                        class="form-input text-sm py-2" placeholder="e.g. fashion, tech, beauty">
                </div>
                <div>
                    <label class="form-label text-xs">Country <span class="text-red-500">*</span></label>
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
            <h3 class="font-bold text-gray-800 mb-4">Account Status</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase">Status</p>
                    <p class="font-semibold mt-0.5 capitalize">{{ $marketer->status }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Type</p>
                    <p class="font-semibold mt-0.5 capitalize">{{ str_replace('_', ' ', $marketer->type) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Referral Code</p>
                    <p class="font-mono font-semibold mt-0.5">{{ $marketer->referral_code }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Commission Rate</p>
                    <p class="font-semibold mt-0.5">{{ $marketer->commission_rate }}%</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Followers</p>
                    <p class="font-semibold mt-0.5">{{ $marketer->followers_count ? number_format($marketer->followers_count) : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Engagement Rate</p>
                    <p class="font-semibold mt-0.5">{{ $marketer->engagement_rate ? $marketer->engagement_rate . '%' : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Total Earnings</p>
                    <p class="font-semibold mt-0.5">{{ number_format($marketer->total_earnings_cents / 100, 2) }} {{ $marketer->country?->currency_code ?? '' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase">Samples Used</p>
                    <p class="font-semibold mt-0.5">{{ $marketer->total_samples_used }} / {{ $marketer->total_samples_allocated ?: '∞' }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4">
                Followers count and engagement rate are set during application review.
                Contact support to request an update if these are outdated.
            </p>
        </div>

        {{-- Public slug --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">Public Profile URL</h3>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-400 font-mono">{{ 'https://marketer.' . env('APP_DOMAIN', 'localhost') }}/p/</span>
                <input type="text" name="boutiqaat_style_slug" id="slug-input"
                    value="{{ old('boutiqaat_style_slug', $marketer->boutiqaat_style_slug) }}"
                    class="form-input flex-1 text-sm py-2 font-mono"
                    placeholder="your-unique-slug" pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, hyphens">
            </div>
            <a id="slug-preview-link" href="#" target="_blank"
               class="text-xs text-blue-600 hover:underline mt-2 inline-block"
               style="{{ $marketer->boutiqaat_style_slug ? '' : 'display:none' }}">
                View public profile →
            </a>
        </div>

        {{-- Photos --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">Photos</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">Profile Photo</label>
                    <img id="preview-profile-photo"
                         src="{{ $marketer->profile_photo_path ? Storage::url($marketer->profile_photo_path) : '' }}"
                         class="w-20 h-20 rounded-full object-cover border border-gray-200 mb-2"
                         style="{{ $marketer->profile_photo_path ? '' : 'display:none' }}" alt="">
                    <input type="file" name="profile_photo" accept="image/*" class="form-input text-sm py-1.5"
                           onchange="previewFile(this, 'preview-profile-photo')">
                </div>
                <div>
                    <label class="form-label text-xs">Banner Image</label>
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
            <h3 class="font-bold text-gray-800 mb-4">Bio & Content</h3>
            <div class="space-y-4">
                <div>
                    <label class="form-label">Short Bio</label>
                    <textarea name="bio" id="bio-input" rows="3" class="form-input text-sm py-2"
                        placeholder="Tell your audience about yourself..."
                        maxlength="2000" oninput="updateCounter(this, 'bio-counter', 2000)">{{ old('bio', $marketer->bio) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1 text-right">
                        <span id="bio-counter">{{ mb_strlen(old('bio', $marketer->bio ?? '')) }}</span> / 2000
                    </p>
                </div>
                <div>
                    <label class="form-label">Promo Content</label>
                    <textarea name="promo_content" rows="4" class="form-input text-sm py-2"
                        placeholder="Detailed content for your profile page..."
                        maxlength="3000" oninput="updateCounter(this, 'promo-counter', 3000)">{{ old('promo_content', $marketer->promo_content) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1 text-right">
                        <span id="promo-counter">{{ mb_strlen(old('promo_content', $marketer->promo_content ?? '')) }}</span> / 3000
                    </p>
                </div>
                <div>
                    <label class="form-label">Featured Video URL</label>
                    <input type="url" name="profile_video_url"
                        value="{{ old('profile_video_url', $marketer->profile_video_url) }}"
                        class="form-input text-sm py-2" placeholder="https://youtube.com/watch?v=...">
                </div>
            </div>
        </div>

        {{-- Social links --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">Social Links</h3>
            <div class="space-y-3">
                @foreach([
                    ['social_instagram', 'Instagram', 'https://instagram.com/username'],
                    ['social_tiktok',    'TikTok',    'https://tiktok.com/@username'],
                    ['social_youtube',   'YouTube',   'https://youtube.com/@username'],
                    ['social_twitter',   'Twitter / X', 'https://x.com/username'],
                    ['social_facebook',  'Facebook',  'https://facebook.com/username'],
                ] as [$field, $label, $placeholder])
                    <div>
                        <label class="form-label text-xs">{{ $label }}</label>
                        <input type="url" name="{{ $field }}"
                            value="{{ old($field, $marketer->$field) }}"
                            class="form-input text-sm py-2" placeholder="{{ $placeholder }}">
                    </div>
                @endforeach
                <div>
                    <label class="form-label text-xs">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number"
                        value="{{ old('whatsapp_number', $marketer->whatsapp_number) }}"
                        class="form-input text-sm py-2" placeholder="+966 5X XXX XXXX">
                </div>
            </div>
        </div>

        {{-- Payout details --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">Payout Details</h3>
            <div class="space-y-3">
                <div>
                    <label class="form-label text-xs">Bank Name</label>
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
                    <label class="form-label text-xs">Account Holder Name</label>
                    <input type="text" name="bank_account_name"
                        value="{{ old('bank_account_name', $marketer->bank_account_name) }}"
                        class="form-input text-sm py-2">
                </div>
            </div>
        </div>

        <button type="submit" id="submit-btn" class="btn btn-primary w-full">Save Profile</button>
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
        btn.textContent = 'Saving...';
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
                showError(data.message || 'Validation error.');
            }
        } catch (_) {
            showError('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Profile';
        }
    });
})();
</script>
@endpush
