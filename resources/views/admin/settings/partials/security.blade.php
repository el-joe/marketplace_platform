{{-- Security Settings Partial --}}
<x-card>
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Security &amp; Access</h2>
        <p class="text-sm text-gray-500">Login protection, session timeouts, and API rate limits.</p>
    </div>
    <div class="divide-y divide-gray-100">

        @foreach($settings->whereIn('key', [
                'max_login_attempts',
                'login_lockout_minutes',
                'admin_session_timeout_minutes',
                'require_2fa_admins',
                'password_min_length',
                'api_rate_limit_per_minute',
                'sanctum_token_expiry_days_web',
                'sanctum_token_expiry_days_app',
            ]) as $setting)
                @include('admin.settings.partials._field', ['setting' => $setting])
        @endforeach

    </div>
</x-card>
