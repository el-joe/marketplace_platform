@extends('layouts.carrier')

@section('title', 'إضافة مندوب')

@section('content')

<div class="mb-6">
    <a href="{{ route('carrier.agents.index') }}" class="text-indigo-600 hover:underline text-sm">← العودة للمناديب</a>
    <h1 class="text-2xl font-black text-gray-900 mt-2">إضافة مندوب جديد</h1>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-8 max-w-2xl">
    <form method="POST" action="{{ route('carrier.agents.store') }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">الاسم الكامل</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">البريد الإلكتروني</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('email') border-red-400 @enderror">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">رقم الجوال</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('phone') border-red-400 @enderror">
                @error('phone')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">نوع المركبة</label>
                <select name="vehicle_type" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">اختر...</option>
                    @foreach(['motorcycle'=>'دراجة نارية','car'=>'سيارة','van'=>'فان','bicycle'=>'دراجة'] as $val=>$label)
                    <option value="{{ $val }}" @selected(old('vehicle_type')===$val)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('vehicle_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">رقم الهوية الوطنية</label>
                <input type="text" name="national_id" value="{{ old('national_id') }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">كلمة المرور</label>
                <input type="password" name="password" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('password') border-red-400 @enderror">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2.5 rounded-lg transition text-sm">
                إضافة المندوب
            </button>
            <a href="{{ route('carrier.agents.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2.5 rounded-lg transition text-sm">
                إلغاء
            </a>
        </div>
    </form>
</div>

@endsection
