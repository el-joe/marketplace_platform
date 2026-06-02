@extends('layouts.marketer')

@section('title', 'Edit Profile')
@section('page-title', 'Edit Profile')

@section('content')

<div class="max-w-2xl mx-auto" x-data="profileEditor()">

    {{-- Success / Error --}}
    <div x-show="saved" x-cloak
         class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-5 text-sm font-medium">
        ✓ Profile saved successfully.
    </div>
    <div x-show="error" x-cloak
         class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm font-medium"
         x-text="error"></div>

    <form @submit.prevent="save()" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Public slug --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">Public Profile URL</h3>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-400 font-mono">{{ 'https://marketer.' . env('APP_DOMAIN', 'localhost') }}/p/</span>
                <input type="text" name="boutiqaat_style_slug"
                    value="{{ old('boutiqaat_style_slug', $marketer->boutiqaat_style_slug) }}"
                    class="form-input flex-1 text-sm py-2 font-mono"
                    placeholder="your-unique-slug" pattern="[a-z0-9\-]+" title="Lowercase letters, numbers, hyphens">
            </div>
            @if($marketer->boutiqaat_style_slug)
                <a href="{{ route('marketer.profile.public', $marketer->boutiqaat_style_slug) }}" target="_blank"
                    class="text-xs text-blue-600 hover:underline mt-2 inline-block">
                    View public profile →
                </a>
            @endif
        </div>

        {{-- Photos --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">Photos</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label text-xs">Profile Photo</label>
                    @if($marketer->profile_photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($marketer->profile_photo_path) }}"
                            class="w-20 h-20 rounded-full object-cover border border-gray-200 mb-2" alt="">
                    @endif
                    <input type="file" name="profile_photo" accept="image/*" class="form-input text-sm py-1.5">
                </div>
                <div>
                    <label class="form-label text-xs">Banner Image</label>
                    @if($marketer->profile_banner_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($marketer->profile_banner_path) }}"
                            class="w-full h-16 rounded-lg object-cover border border-gray-200 mb-2" alt="">
                    @endif
                    <input type="file" name="profile_banner" accept="image/*" class="form-input text-sm py-1.5">
                </div>
            </div>
        </div>

        {{-- Bio and content --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-5">
            <h3 class="font-bold text-gray-800 mb-4">Bio & Content</h3>
            <div class="space-y-4">
                <div>
                    <label class="form-label">Short Bio</label>
                    <textarea name="bio" rows="3" class="form-input text-sm py-2"
                        placeholder="Tell your audience about yourself..."
                        maxlength="2000">{{ old('bio', $marketer->bio) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Promo Content</label>
                    <textarea name="promo_content" rows="4" class="form-input text-sm py-2"
                        placeholder="Detailed content for your profile page..."
                        maxlength="3000">{{ old('promo_content', $marketer->promo_content) }}</textarea>
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

        <button type="submit" :disabled="loading"
            class="btn btn-primary w-full"
            x-text="loading ? 'Saving...' : 'Save Profile'"></button>
    </form>
</div>

@endsection

@push('scripts')
<script>
function profileEditor() {
    return {
        loading: false, saved: false, error: null,
        async save() {
            this.loading = true; this.saved = false; this.error = null;
            const form = document.querySelector('form');
            const fd = new FormData(form);
            fd.append('_method', 'PUT');
            try {
                const res = await fetch('{{ route('marketer.profile.update') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await res.json();
                if (data.success) { this.saved = true; window.scrollTo(0,0); }
                else { this.error = data.message || 'Validation error'; }
            } catch (e) { this.error = 'Network error'; }
            finally { this.loading = false; }
        }
    };
}
</script>
@endpush
