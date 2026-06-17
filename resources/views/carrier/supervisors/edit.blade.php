@extends('layouts.carrier')

@section('title', 'تعديل مشرف')

@section('content')

<div class="mb-6">
    <a href="{{ route('carrier.supervisors.index') }}" class="text-indigo-600 hover:underline text-sm">← العودة للمشرفين</a>
    <h1 class="text-2xl font-black text-gray-900 mt-2">تعديل: {{ $editSupervisor->name }}</h1>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-8 max-w-2xl">
    <form method="POST" action="{{ route('carrier.supervisors.update', $editSupervisor->id) }}" class="space-y-5">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">الاسم الكامل</label>
                <input type="text" name="name" value="{{ old('name', $editSupervisor->name) }}" required
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">رقم الجوال</label>
                <input type="text" name="phone" value="{{ old('phone', $editSupervisor->phone) }}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">الصلاحيات</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach($allPermissions as $perm)
                @php
                    $labels = ['manage_agents'=>'إدارة المناديب','view_orders'=>'عرض الطلبات','assign_orders'=>'تعيين الطلبات','view_reports'=>'عرض التقارير'];
                    $current = old('permissions', $editSupervisor->permissions ?? []);
                @endphp
                <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-indigo-50 transition">
                    <input type="checkbox" name="permissions[]" value="{{ $perm }}"
                           class="rounded border-gray-300 text-indigo-600"
                           @checked(in_array($perm, $current))>
                    <span class="text-sm text-gray-700">{{ $labels[$perm] ?? $perm }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded border-gray-300 text-indigo-600"
                   @checked(old('is_active', $editSupervisor->is_active))>
            <label for="is_active" class="text-sm font-semibold text-gray-700">الحساب نشط</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2.5 rounded-lg transition text-sm">
                حفظ التعديلات
            </button>
            <a href="{{ route('carrier.supervisors.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-6 py-2.5 rounded-lg transition text-sm">
                إلغاء
            </a>
        </div>
    </form>
</div>

@endsection
