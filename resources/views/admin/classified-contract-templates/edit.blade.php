@extends('layouts.admin')

@section('title', 'Edit Template: ' . $template->name)

@section('content')
<div class="p-6 max-w-3xl mx-auto space-y-4">
    <h1 class="text-xl font-bold text-gray-900">Edit: {{ $template->name }}</h1>

    <form method="POST" action="{{ route('admin.classifieds.contract-templates.update', $template) }}"
          class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf @method('PUT')
        @include('admin.classified-contract-templates._form', ['template' => $template])
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.classifieds.contract-templates.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</a>
            <button type="submit"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium">Save</button>
        </div>
    </form>
</div>
@endsection
