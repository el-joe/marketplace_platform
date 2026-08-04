<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        /** @var DeliveryAgent $agent */
        $agent = Auth::guard('delivery')->user()->load('documents', 'zone.country');

        return view('delivery.profile.index', compact('agent'));
    }

    public function changePassword(Request $request): RedirectResponse
    {
        /** @var DeliveryAgent $agent */
        $agent = Auth::guard('delivery')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($validated['current_password'], $agent->password)) {
            return back()->withErrors(['current_password' => __('delivery.messages.profile.current_password_incorrect')]);
        }

        $agent->update(['password' => $validated['password']]);

        return back()->with('success', __('delivery.messages.profile.password_updated'));
    }
}
