<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $marketer->name }} — Creator Profile</title>
    @vite(['resources/css/app.css'])
    <style>
        body { background: #f1f5f9; font-family: 'Inter', sans-serif; }
        .hero-banner {
            height: 260px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-banner.has-image { background: none; }
        .hero-banner img.banner-img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-ring {
            width: 104px; height: 104px;
            border: 4px solid #fff;
            border-radius: 50%;
            position: absolute;
            bottom: -52px; left: 50%;
            transform: translateX(-50%);
            overflow: hidden;
            background: #e2e8f0;
        }
        .avatar-ring img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-placeholder {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; font-weight: 700; color: #94a3b8;
            background: #e2e8f0;
        }
        .social-link {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.35rem 0.85rem; border-radius: 9999px;
            font-size: 0.75rem; font-weight: 600; text-decoration: none;
            transition: opacity 0.15s;
        }
        .social-link:hover { opacity: 0.8; }
        .product-tile {
            background: #fff; border-radius: 1rem; overflow: hidden;
            border: 1px solid #e2e8f0; transition: box-shadow 0.15s;
        }
        .product-tile:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

{{-- Hero Banner --}}
<div class="hero-banner {{ $marketer->profile_banner_path ? 'has-image' : '' }}">
    @if($marketer->profile_banner_path)
        <img class="banner-img" src="{{ \Illuminate\Support\Facades\Storage::url($marketer->profile_banner_path) }}" alt="">
    @else
        {{-- Decorative pattern --}}
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 32px 32px;"></div>
    @endif
    <div class="avatar-ring">
        @if($marketer->profile_photo_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::url($marketer->profile_photo_path) }}" alt="{{ $marketer->name }}">
        @else
            <div class="avatar-placeholder">{{ mb_substr($marketer->name, 0, 1) }}</div>
        @endif
    </div>
</div>

{{-- Profile Card --}}
<div class="max-w-2xl mx-auto px-4">
    <div class="bg-white rounded-2xl border border-gray-200 pt-16 pb-6 px-6 text-center -mt-2 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">{{ $marketer->name }}</h1>
        <div class="flex items-center justify-center gap-2 mt-1">
            <span class="text-xs font-semibold uppercase tracking-wide px-2.5 py-0.5 rounded-full
                {{ $marketer->marketer_type === 'influencer' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                {{ ucfirst($marketer->marketer_type ?? 'Creator') }}
            </span>
        </div>

        @if($marketer->bio)
            <p class="text-sm text-gray-600 mt-3 leading-relaxed max-w-md mx-auto">{{ $marketer->bio }}</p>
        @endif

        {{-- Social Links --}}
        @php
            $socials = array_filter([
                'instagram' => ['label' => 'Instagram', 'color' => 'bg-gradient-to-r from-purple-500 to-pink-500 text-white', 'url' => $marketer->social_instagram],
                'tiktok'    => ['label' => 'TikTok',    'color' => 'bg-black text-white', 'url' => $marketer->social_tiktok],
                'youtube'   => ['label' => 'YouTube',   'color' => 'bg-red-600 text-white', 'url' => $marketer->social_youtube],
                'twitter'   => ['label' => 'Twitter',   'color' => 'bg-sky-500 text-white', 'url' => $marketer->social_twitter],
                'facebook'  => ['label' => 'Facebook',  'color' => 'bg-blue-600 text-white', 'url' => $marketer->social_facebook],
            ], fn($s) => !empty($s['url']));
        @endphp
        @if(count($socials) > 0)
            <div class="flex flex-wrap justify-center gap-2 mt-4">
                @foreach($socials as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                       class="social-link {{ $social['color'] }}">
                        {{ $social['label'] }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- WhatsApp contact --}}
        @if($marketer->whatsapp_number)
            <div class="mt-4">
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $marketer->whatsapp_number) }}"
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-400 text-white font-semibold text-sm rounded-xl px-5 py-2.5 transition-colors">
                    📱 Contact on WhatsApp
                </a>
            </div>
        @endif
    </div>

    {{-- Promo Content --}}
    @if($marketer->promo_content)
        <div class="bg-white rounded-2xl border border-gray-200 p-5 mt-5">
            <h2 class="font-bold text-gray-800 mb-2">About</h2>
            <div class="text-sm text-gray-600 leading-relaxed">{{ $marketer->promo_content }}</div>
        </div>
    @endif

    {{-- Video --}}
    @if($marketer->profile_video_url)
        <div class="bg-white rounded-2xl border border-gray-200 p-5 mt-5">
            <h2 class="font-bold text-gray-800 mb-3">Featured Video</h2>
            @php
                // Convert YouTube watch URL to embed URL
                $videoUrl = $marketer->profile_video_url;
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $videoUrl, $m)) {
                    $videoUrl = 'https://www.youtube.com/embed/' . $m[1];
                }
            @endphp
            <div class="relative w-full" style="padding-top: 56.25%">
                <iframe class="absolute inset-0 w-full h-full rounded-xl"
                    src="{{ $videoUrl }}" frameborder="0"
                    allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    @endif

    {{-- Featured Products --}}
    @if($campaigns->isNotEmpty())
        <div class="mt-5 mb-10">
            <h2 class="font-bold text-gray-800 mb-4 text-lg">Featured Products</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($campaigns as $campaign)
                    @foreach($campaign->products->take(6) as $cp)
                        @php
                            $listing  = $cp->vendorListing;
                            $product  = $listing?->product;
                            $country  = env('DEFAULT_COUNTRY_SLUG', 'sa');
                            $domain   = env('APP_DOMAIN', 'localhost');
                            $slug     = $campaign->tracking_url_slug;
                            $ref      = $marketer->referral_code;
                            $link     = 'https://' . $country . '.' . $domain . '/r/' . $slug . '?ref=' . $ref;
                        @endphp
                        @if($product)
                        <a href="{{ $link }}" class="product-tile block" target="_blank" rel="noopener">
                            @if($product->main_image_url ?? false)
                                <img src="{{ $product->main_image_url }}" alt="{{ $product->name_en }}"
                                    class="w-full aspect-square object-cover">
                            @else
                                <div class="w-full aspect-square bg-gray-100 flex items-center justify-center text-gray-300 text-3xl">📦</div>
                            @endif
                            <div class="p-3">
                                <p class="text-sm font-medium text-gray-800 line-clamp-2 leading-snug">{{ $product->name_en }}</p>
                                @if($listing?->sale_price)
                                    <p class="text-sm font-bold text-gray-900 mt-1">{{ number_format($listing->sale_price / 100, 2) }} SAR</p>
                                @endif
                            </div>
                        </a>
                        @endif
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif
</div>

</body>
</html>
