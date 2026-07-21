<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('marketer.register.page_title') }}</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .register-card {
            background: #1e293b;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 680px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .section-heading {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #facc15;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #334155;
        }

        .form-input-dark {
            width: 100%;
            background: #0f172a;
            border: 1.5px solid #334155;
            color: #f1f5f9;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: border-color 0.15s;
            box-sizing: border-box;
        }

        .form-input-dark:focus {
            outline: none;
            border-color: #facc15;
        }

        .form-input-dark::placeholder {
            color: #475569;
        }

        .form-input-dark.is-invalid {
            border-color: #f87171;
        }

        .form-grid {
            display: grid;
            gap: 1rem;
        }

        .form-grid-2 {
            grid-template-columns: 1fr 1fr;
        }

        @media (max-width: 560px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }

        .field-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 0.4rem;
        }

        .field-error {
            font-size: 0.72rem;
            color: #f87171;
            margin-top: 0.25rem;
        }

        .btn-primary-yellow {
            width: 100%;
            background: #facc15;
            color: #0f172a;
            font-weight: 700;
            border: none;
            border-radius: 0.75rem;
            padding: 0.875rem;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-primary-yellow:hover {
            background: #fde047;
        }

        select.form-input-dark option {
            background: #1e293b;
            color: #f1f5f9;
        }
    </style>
</head>

<body>
    <div class="register-card">

        {{-- Logo --}}
        <div class="text-center mb-7">
            <h1 style="font-size:1.75rem;font-weight:900;color:#facc15;letter-spacing:-1px;">noon</h1>
            <p style="color:#94a3b8;font-size:0.8rem;margin-top:2px;">{{ __('marketer.register.subtitle') }}</p>
            <p style="color:#64748b;font-size:0.78rem;margin-top:6px;">{{ __('marketer.register.intro') }}</p>
        </div>

        {{-- Success message --}}
        @if(session('success'))
            <div
                style="background:#14532d;border:1px solid #166534;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1.5rem;">
                <p style="color:#86efac;font-size:0.85rem;">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Validation errors summary --}}
        @if($errors->any())
            <div
                style="background:#7f1d1d;border:1px solid #991b1b;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1.5rem;">
                <p style="color:#fca5a5;font-size:0.85rem;font-weight:600;margin-bottom:0.25rem;">{{ __('marketer.register.fix_errors') }}
                </p>
                <ul style="margin:0;padding-left:1.2rem;">
                    @foreach($errors->all() as $error)
                        <li style="color:#fca5a5;font-size:0.8rem;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('marketer.register.post') }}">
            @csrf

            {{-- ── Section 1: Account ──────────────────────────────────────── --}}
            <p class="section-heading">{{ __('marketer.register.section_account') }}</p>
            <div class="form-grid form-grid-2 mb-5">
                <div>
                    <label class="field-label">{{ __('marketer.register.full_name') }} <span style="color:#f87171;">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="form-input-dark @error('name') is-invalid @enderror" placeholder="Sara Ahmed" required
                        autofocus>
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.email') }} <span style="color:#f87171;">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-input-dark @error('email') is-invalid @enderror" placeholder="you@example.com"
                        required>
                    @error('email') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.password') }} <span style="color:#f87171;">*</span></label>
                    <input type="password" name="password"
                        class="form-input-dark @error('password') is-invalid @enderror" placeholder="{{ __('marketer.register.password_placeholder') }}"
                        required minlength="8" autocomplete="new-password">
                    @error('password') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.confirm_password') }} <span style="color:#f87171;">*</span></label>
                    <input type="password" name="password_confirmation" class="form-input-dark"
                        placeholder="{{ __('marketer.register.confirm_password_placeholder') }}" required minlength="8">
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.phone') }}</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="form-input-dark @error('phone') is-invalid @enderror" placeholder="+20 10 0000 0000">
                    @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.whatsapp') }}</label>
                    <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}"
                        class="form-input-dark @error('whatsapp_number') is-invalid @enderror"
                        placeholder="+20 10 0000 0000">
                    @error('whatsapp_number') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ── Section 2: Profile ──────────────────────────────────────── --}}
            <p class="section-heading">{{ __('marketer.register.section_profile') }}</p>
            <div class="form-grid form-grid-2 mb-5">
                <div>
                    <label class="field-label">{{ __('marketer.register.marketer_type') }} <span style="color:#f87171;">*</span></label>
                    <select name="type" class="form-input-dark @error('type') is-invalid @enderror" required>
                        <option value="">{{ __('marketer.register.select_type') }}</option>
                        <option value="influencer" {{ old('type') === 'influencer' ? 'selected' : '' }}>{{ __('marketer.register.type_influencer') }}
                        </option>
                        <option value="celebrity" {{ old('type') === 'celebrity' ? 'selected' : '' }}>{{ __('marketer.register.type_celebrity') }}</option>
                        <option value="affiliate" {{ old('type') === 'affiliate' ? 'selected' : '' }}>{{ __('marketer.register.type_affiliate') }}</option>
                        <option value="brand_ambassador" {{ old('type') === 'brand_ambassador' ? 'selected' : '' }}>{{ __('marketer.register.type_brand_ambassador') }}</option>
                    </select>
                    @error('type') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.country') }}</label>
                    <select name="country_id" class="form-input-dark @error('country_id') is-invalid @enderror">
                        <option value="">{{ __('marketer.register.select_country') }}</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>
                                {{ $country->name_en }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.niche') }}</label>
                    <input type="text" name="niche" value="{{ old('niche') }}"
                        class="form-input-dark @error('niche') is-invalid @enderror"
                        placeholder="{{ __('marketer.register.niche_placeholder') }}">
                    @error('niche') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.followers_count') }}</label>
                    <input type="number" name="followers_count" value="{{ old('followers_count') }}"
                        class="form-input-dark @error('followers_count') is-invalid @enderror" min="0"
                        placeholder="{{ __('marketer.register.followers_count_placeholder') }}">
                    @error('followers_count') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div style="grid-column: 1 / -1;">
                    <label class="field-label">{{ __('marketer.register.bio') }} <span style="color:#64748b;">{{ __('marketer.register.bio_optional') }}</span></label>
                    <textarea name="bio" rows="3" class="form-input-dark @error('bio') is-invalid @enderror"
                        placeholder="{{ __('marketer.register.bio_placeholder') }}"
                        maxlength="1000">{{ old('bio') }}</textarea>
                    @error('bio') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ── Section 3: Social Links ─────────────────────────────────── --}}
            <p class="section-heading">{{ __('marketer.register.section_social') }}</p>
            <div class="form-grid form-grid-2 mb-6">
                <div>
                    <label class="field-label">{{ __('marketer.register.instagram_url') }}</label>
                    <input type="url" name="social_instagram" value="{{ old('social_instagram') }}"
                        class="form-input-dark @error('social_instagram') is-invalid @enderror"
                        placeholder="https://instagram.com/yourhandle">
                    @error('social_instagram') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.tiktok_url') }}</label>
                    <input type="url" name="social_tiktok" value="{{ old('social_tiktok') }}"
                        class="form-input-dark @error('social_tiktok') is-invalid @enderror"
                        placeholder="https://tiktok.com/@yourhandle">
                    @error('social_tiktok') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label">{{ __('marketer.register.youtube_url') }}</label>
                    <input type="url" name="social_youtube" value="{{ old('social_youtube') }}"
                        class="form-input-dark @error('social_youtube') is-invalid @enderror"
                        placeholder="https://youtube.com/@yourchannel">
                    @error('social_youtube') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Terms --}}
            <div style="display:flex;align-items:flex-start;gap:0.6rem;margin-bottom:1.5rem;">
                <input type="checkbox" name="terms" id="terms" value="1" {{ old('terms') ? 'checked' : '' }}
                    style="accent-color:#facc15;margin-top:3px;flex-shrink:0;">
                <label for="terms" style="font-size:0.8rem;color:#94a3b8;cursor:pointer;line-height:1.5;">
                    {{ __('marketer.register.terms_prefix') }} <a href="#" style="color:#facc15;">{{ __('marketer.register.terms_link_text') }}</a> {{ __('marketer.register.terms_suffix') }}
                </label>
            </div>
            @error('terms')
                <p class="field-error" style="margin-top:-1rem;margin-bottom:1rem;">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn-primary-yellow">{{ __('marketer.register.submit') }}</button>
        </form>

        <p style="text-align:center;font-size:0.75rem;color:#475569;margin-top:1.5rem;">
            {{ __('marketer.register.have_account') }}
            <a href="{{ route('marketer.login') }}" style="color:#facc15;">{{ __('marketer.register.sign_in') }}</a>
        </p>
    </div>
</body>

</html>