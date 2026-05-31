<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>نسيت كلمة المرور | نون للبائعين</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cairo:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/partner/app.js'])
</head>
<body class="min-h-screen bg-gray-950 flex items-center justify-center p-4" style="font-family: 'Cairo', sans-serif;">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <span class="inline-block bg-yellow-400 text-gray-950 font-black text-3xl px-4 py-1 rounded-lg">noon</span>
            <p class="text-gray-400 text-sm mt-3">لوحة تحكم البائعين</p>
        </div>

        {{-- Card --}}
        <div class="bg-gray-900 rounded-2xl border border-gray-800 p-8 shadow-2xl">

            <h1 class="text-white text-xl font-bold mb-2">نسيت كلمة المرور؟</h1>
            <p class="text-gray-400 text-sm mb-6">أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.</p>

            {{-- Status message --}}
            @if(session('status'))
                <div class="mb-4 p-4 bg-green-500/10 border border-green-500/30 rounded-xl text-green-400 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('partner.auth.forgot.send') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-300 mb-1.5">البريد الإلكتروني</label>
                    <input id="email" name="email" type="email" required autofocus
                           value="{{ old('email') }}"
                           class="w-full bg-gray-800 border @error('email') border-red-500 @else border-gray-700 @enderror
                                  rounded-xl px-4 py-3 text-white placeholder-gray-500 text-sm
                                  focus:outline-none focus:ring-2 focus:ring-yellow-400/50 focus:border-yellow-400 transition-colors"
                           placeholder="example@store.com">
                    @error('email')
                        <p class="text-red-400 text-xs mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full bg-yellow-400 hover:bg-yellow-300 text-gray-950 font-bold py-3 rounded-xl
                               transition-colors text-sm">
                    إرسال رابط الاسترداد
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('partner.login') }}" class="text-sm text-gray-400 hover:text-white transition-colors">
                    ← العودة إلى تسجيل الدخول
                </a>
            </div>

        </div>
    </div>

</body>
</html>
