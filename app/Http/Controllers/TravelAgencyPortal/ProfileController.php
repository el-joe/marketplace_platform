<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $agency = Auth::guard('travel_agency')->user();

        return view('travel-agency.profile.edit', compact('agency'));
    }

    public function update(Request $request): RedirectResponse
    {
        $agency = Auth::guard('travel_agency')->user();

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'phone'          => ['nullable', 'string', 'max:30'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'logo'           => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        if ($request->hasFile('logo')) {
            if ($agency->logo_path) {
                Storage::disk('public')->delete($agency->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('travel-agency-logos', 'public');
        }

        unset($data['logo']);

        $agency->update($data);

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }
}
