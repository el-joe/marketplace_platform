<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\Marketer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        if ($marketer->status === 'pending') {
            Auth::guard('marketer')->logout();
            return back()->withErrors(['email' => 'Your account is pending approval. You will be notified once approved.'])->withInput();
        }

        if (in_array($marketer->status, ['suspended', 'rejected'])) {
            Auth::guard('marketer')->logout();
            return back()->withErrors(['email' => 'Your account is ' . $marketer->status . '. Contact support.'])->withInput();
        }

        $marketer->update([
            'last_login_at' => now(),
        ]);

        $request->session()->regenerate();

        return redirect()->route('marketer.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('marketer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('marketer.login');
    }
}
