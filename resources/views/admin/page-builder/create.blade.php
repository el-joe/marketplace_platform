@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/select2.js', 'resources/js/components/slug-input.js', 'resources/js/admin/page-builder.js'])
@endpush

@section('title', 'New Page')

@section('content')
    <form id="create-page-form">
        @csrf

        <div class="flex gap-6 items-start">

            {{-- Main column --}}
            <div class="flex-1 min-w-0 space-y-6">

                <x-card title="Page Details">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Page Name <span class="text-danger-500">*</span></label>
                            <input type="text" name="name" id="page-name" class="form-input w-full"
                                placeholder="e.g. Summer Sale 2026" required>
                        </div>
                        <div>
                            <label class="form-label">Slug <span class="text-danger-500">*</span></label>
                            <x-slug-input name="slug" :source="'page-name'" />
                        </div>
                        <div>
                            <label class="form-label">Page Type <span class="text-danger-500">*</span></label>
                            <select name="page_type" class="form-select w-full" required>
                                <option value="">— Select type —</option>
                                <option value="home">Home</option>
                                <option value="category">Category</option>
                                <option value="brand">Brand</option>
                                <option value="landing">Landing</option>
                                <option value="campaign">Campaign</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Country <span class="text-danger-500">*</span></label>
                            <select name="country_id" class="form-select w-full" data-select2 required>
                                <option value="">— Select country —</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-card>

                <x-card title="SEO">
                    <div class="space-y-4">
                        <div>
                            <label class="form-label">SEO Title</label>
                            <input type="text" name="seo_title" class="form-input w-full" maxlength="255">
                        </div>
                        <div>
                            <label class="form-label">SEO Description</label>
                            <textarea name="seo_description" rows="3" class="form-textarea w-full"
                                maxlength="500"></textarea>
                        </div>
                    </div>
                </x-card>

            </div>

            {{-- Sidebar --}}
            <div class="w-64 flex-shrink-0 space-y-4 sticky top-20">
                <x-card>
                    <div class="space-y-2">
                        <x-form-toggle name="is_default" label="Set as Default" :value="false" />
                    </div>
                </x-card>
                <x-card>
                    <div class="space-y-2">
                        <button type="submit" class="btn btn-primary w-full justify-center">
                            Create &amp; Open Editor
                        </button>
                        <a href="{{ route('admin.page-builder.index') }}" class="btn btn-ghost w-full justify-center">
                            Cancel
                        </a>
                    </div>
                </x-card>
            </div>

        </div>
    </form>
@endsection