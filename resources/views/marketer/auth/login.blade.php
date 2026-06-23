<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketer Login — Noon</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #1e293b;
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
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
        }

        .form-input-dark:focus {
            outline: none;
            border-color: #facc15;
        }

        .form-input-dark::placeholder {
            color: #475569;
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
    </style>
</head>

<body>
    <div class="login-card">
        <div class="text-center mb-8">
            <h1 style="font-size:1.75rem;font-weight:900;color:#facc15;letter-spacing:-1px;">noon</h1>
            <p style="color:#94a3b8;font-size:0.8rem;margin-top:2px;">Marketer &amp; Influencer Portal</p>
        </div>

        <form method="POST" action="{{ route('marketer.login.post') }}">
            @csrf

            @if($errors->any())
                <div
                    style="background:#7f1d1d;border:1px solid #991b1b;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1.25rem;">
                    <p style="color:#fca5a5;font-size:0.85rem;">{{ $errors->first() }}</p>
                </div>
            @endif

            <div class="mb-4">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:#94a3b8;margin-bottom:0.4rem;">
                    Email Address
                </label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input-dark"
                    placeholder="you@example.com" required autofocus>
            </div>

            <div class="mb-5">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:#94a3b8;margin-bottom:0.4rem;">
                    Password
                </label>
                <input type="password" name="password" class="form-input-dark" placeholder="••••••••" required>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" name="remember" style="accent-color:#facc15;">
                    <span style="font-size:0.8rem;color:#94a3b8;">Keep me signed in</span>
                </label>
            </div>

            <button type="submit" class="btn-primary-yellow">Sign In</button>
        </form>

        <p style="text-align:center;font-size:0.75rem;color:#475569;margin-top:1.5rem;">
            Not a marketer yet? Contact <a href="mailto:partners@noon.com" style="color:#facc15;">partners@noon.com</a>
        </p>
    </div>
</body>

</html>
