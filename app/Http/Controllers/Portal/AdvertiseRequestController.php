<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AdvertiseInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdvertiseRequestController extends Controller
{
    public function store(Request $request, string $country): RedirectResponse
    {
        $isAr = session('locale', 'ar') === 'ar';

        $noSpecialChars = 'regex:/^[^+\-*\/]*$/';

        $messages = $isAr ? [
            'name.regex' => 'الأحرف الخاصة (+، -، *، /) غير مسموح بها. يرجى إزالتها.',
            'company_name.regex' => 'الأحرف الخاصة (+، -، *، /) غير مسموح بها. يرجى إزالتها.',
            'phone.regex' => 'يرجى إدخال رقم جوال صحيح!',
            'description.min' => 'يرجى التأكد من أن الوصف يحتوي على 50 حرفاً على الأقل.',
            'description.max' => 'يرجى التأكد من أن الوصف أقل من 1000 حرف.',
            'name.required' => 'يرجى إدخال اسمك الكامل!',
            'email.required' => 'يرجى إدخال عنوان بريد إلكتروني صالح!',
            'email.email' => 'يرجى إدخال عنوان بريد إلكتروني صالح!',
            'company_name.required' => 'يرجى إدخال اسم شركتك!',
            'description.required' => 'يرجى إدخال تفاصيل الطلب في الوصف!',
        ] : [
            'name.regex' => 'Special characters (+, -, *, /) are not allowed. Please remove them.',
            'company_name.regex' => 'Special characters (+, -, *, /) are not allowed. Please remove them.',
            'phone.regex' => 'Please enter a valid phone number!',
            'description.min' => 'Please ensure the description is at least 50 characters long.',
            'description.max' => 'Please ensure the description is less than 1000 characters.',
            'name.required' => 'Please enter your full name!',
            'email.required' => 'Please enter a valid email address!',
            'email.email' => 'Please enter a valid email address!',
            'company_name.required' => 'Please enter your company name!',
            'description.required' => 'Please enter your request details!',
        ];

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', $noSpecialChars],
            'email' => ['required', 'email', 'max:255'],
            'company_name' => ['required', 'string', 'max:255', $noSpecialChars],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'description' => ['required', 'string', 'min:50', 'max:1000'],
        ], $messages);

        AdvertiseInquiry::create([
            'country' => $country,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_name' => $validated['company_name'],
            'phone' => $validated['phone'] ?? null,
            'description' => $validated['description'],
        ]);

        return redirect()
            ->route('portal.advertise.request', $country)
            ->with('advertise_request_success', true);
    }
}
