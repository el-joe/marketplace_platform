@extends('layouts.travel-agency')

@section('title', 'الملف الشخصي')

@section('content')
<div class="max-w-2xl space-y-6">
    <h1 class="text-2xl font-black text-gray-900">{{ __('travel.profile.title') }}</h1>

    <form method="POST" action="{{ route('travel-agency.profile.update') }}" enctype="multipart/form-data"
          class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf @method('PUT')

        {{-- Logo --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{  }}</label>
            @if($agency->logo_path)
            <div class="mb-3">
                <img src="{{ asset('storage/'.$agency->logo_path) }}" alt="logo"
                     class="h-16 w-16 rounded-xl object-cover border border-gray-200">
            </div>
            @endif
            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp"
                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            @error('logo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Name --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">اسم الشركة <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $agency->name) }}" required
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Phone --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الهاتف</label>
            <input type="text" name="phone" value="{{ old('phone', $agency->phone) }}"
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- License number --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">رقم الترخيص</label>
            <input type="text" name="license_number" value="{{ old('license_number', $agency->license_number) }}"
                   class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300">
            @error('license_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Read-only fields --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">البريد الإلكتروني</label>
                <input type="text" value="{{ $agency->email }}" disabled
                       class="w-full border border-gray-200 bg-gray-50 rounded-xl px-4 py-2.5 text-sm text-gray-500 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-500 mb-1">حالة الحساب</label>
                @php $statusLabels = ['pending'=>'قيد المراجعة','active'=>'نشط','suspended'=>'موقوف']; @endphp
                <input type="text" value="{{ $statusLabels[$agency->status] ?? $agency->status }}" disabled
                       class="w-full border border-gray-200 bg-gray-50 rounded-xl px-4 py-2.5 text-sm text-gray-500 cursor-not-allowed">
            </div>
        </div>

        <div class="pt-2">
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-500 text-white rounded-xl font-bold hover:bg-blue-400 transition-colors">
                حفظ التغييرات
            </button>
        </div>
    </form>
</div>
@endsection
