@extends('layouts.admin')

@section('title', 'New Classified Category')

@section('content')
<div class="p-6 max-w-2xl mx-auto space-y-4">
    <h1 class="text-xl font-bold text-gray-900">New Classified Category</h1>

    <form method="POST" action="{{ route('admin.classifieds.categories.store') }}"
          class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf
        @include('admin.classified-categories._form', ['category' => null])
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.classifieds.categories.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</a>
            <button type="submit"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium">Create</button>
        </div>
    </form>
</div>
@endsection
