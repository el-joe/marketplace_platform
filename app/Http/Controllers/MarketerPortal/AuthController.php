<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Enums\MarketerStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Marketer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('marketer.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::guard('marketer')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        $marketer = Auth::guard('marketer')->user();

        if ($marketer->status === MarketerStatus::Pending) {
            Auth::guard('marketer')->logout();
            return back()->withErrors(['email' => 'Your account is pending approval. You will be notified once approved.'])->withInput();
        }

        if (in_array($marketer->status, [MarketerStatus::Suspended, MarketerStatus::Rejected], true)) {
            Auth::guard('marketer')->logout();
            return back()->withErrors(['email' => 'Your account is ' . $marketer->status->value . '. Contact support.'])->withInput();
        }

        $marketer->update([
            'last_login_at' => now(),
        ]);

        $request->session()->regenerate();

        return redirect()->route('marketer.dashboard');
    }

    public function showRegister(): View
    {
        $countries = Country::orderBy('name_en')->get(['id', 'name_en']);

        return view('marketer.auth.register', compact('countries'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:marketers,email',
            'phone' => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', Password::min(8)],
            'type' => 'required|in:influencer,celebrity,affiliate,brand_ambassador',
            'country_id' => 'nullable|exists:countries,id',
            'niche' => 'nullable|string|max:100',
            'followers_count' => 'nullable|integer|min:0',
            'bio' => 'nullable|string|max:1000',
            'social_instagram' => 'nullable|url|max:255',
            'social_tiktok' => 'nullable|url|max:255',
            'social_youtube' => 'nullable|url|max:255',
            'whatsapp_number' => 'nullable|string|max:30',
            'terms' => 'accepted',
        ]);

        $marketer = Marketer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'type' => $data['type'],
            'country_id' => $data['country_id'] ?? null,
            'niche' => $data['niche'] ?? null,
            'followers_count' => $data['followers_count'] ?? null,
            'bio' => $data['bio'] ?? null,
            'social_instagram' => $data['social_instagram'] ?? null,
            'social_tiktok' => $data['social_tiktok'] ?? null,
            'social_youtube' => $data['social_youtube'] ?? null,
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('marketer.login')
            ->with('success', 'Your application has been submitted! We will review it and notify you within 24–48 hours.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('marketer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('marketer.login');
    }
}
