@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/flatpickr.js', 'resources/js/components/select2.js', 'resources/js/components/file-upload.js', 'resources/js/admin/flash-sales.js'])
@endpush

@section('title', 'New Flash Sale')

@section('content')
    <form id="create-flash-sale-form">
        @csrf

        <div class="flex gap-6 items-start">

            {{-- ─── Main column ─────────────────────────────────────────────── --}}
            <div class="flex-1 min-w-0 space-y-6">

                {{-- Basic Info --}}
                <x-card title="Basic Information">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Name (English) <span class="text-danger-500">*</span></label>
                            <input type="text" name="name_en" class="form-input w-full" required>
                        </div>
                        <div>
                            <label class="form-label">Name (Arabic) <span class="text-danger-500">*</span></label>
                            <input type="text" name="name_ar" class="form-input w-full text-right" dir="rtl" required>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Description (English)</label>
                            <textarea name="description_en" rows="3" class="form-textarea w-full"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Description (Arabic)</label>
                            <textarea name="description_ar" rows="3" class="form-textarea w-full text-right" dir="rtl"></textarea>
                        </div>
                        <div>
                            <label class="form-label">Country</label>
                            <select name="country_id" class="form-select w-full" data-select2>
                                <option value="">— All Countries —</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name_en }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Banner Image</label>
                            <input type="hidden" name="banner_file_id" id="banner-file-id">
                            <x-file-upload
                                id="banner-upload"
                                accept="image/*"
                                :maxFiles="1"
                                onUpload="function(file){ document.getElementById('banner-file-id').value = file.file_id; }"
                            />
                        </div>
                    </div>
                </x-card>

                {{-- Schedule --}}
                <x-card title="Schedule">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Submission Opens At <span class="text-danger-500">*</span></label>
                            <input type="text" name="submission_opens_at" class="form-input w-full flatpickr-datetime" required>
                        </div>
                        <div>
                            <label class="form-label">Submission Closes At <span class="text-danger-500">*</span></label>
                            <input type="text" name="submission_closes_at" class="form-input w-full flatpickr-datetime" required>
                        </div>
                        <div>
                            <label class="form-label">Review Deadline</label>
                            <input type="text" name="review_deadline_at" class="form-input w-full flatpickr-datetime">
                        </div>
                        <div></div>
                        <div>
                            <label class="form-label">Sale Starts At <span class="text-danger-500">*</span></label>
                            <input type="text" name="sale_starts_at" class="form-input w-full flatpickr-datetime" required>
                        </div>
                        <div>
                            <label class="form-label">Sale Ends At <span class="text-danger-500">*</span></label>
                            <input type="text" name="sale_ends_at" class="form-input w-full flatpickr-datetime" required>
                        </div>
                    </div>
                </x-card>

                {{-- Eligibility Rules --}}
                <x-card title="Eligibility Rules">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Min Discount % <span class="text-danger-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="min_discount_pct" class="form-input w-full pr-8"
                                    min="1" max="100" step="0.5" value="10" required>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Max Products Per Vendor <span class="text-danger-500">*</span></label>
                            <input type="number" name="max_products_per_vendor" class="form-input w-full" min="1" value="5" required>
                        </div>
                        <div>
                            <label class="form-label">Max Total Slots</label>
                            <input type="number" name="max_total_slots" class="form-input w-full" min="1" placeholder="Unlimited">
                        </div>
                        <div>
                            <label class="form-label">Min Vendor Rating</label>
                            <input type="number" name="min_vendor_rating" class="form-input w-full" min="0" max="5" step="0.1" placeholder="e.g. 3.5">
                        </div>
                        <div>
                            <label class="form-label">Commission Override %</label>
                            <div class="relative">
                                <input type="number" name="commission_override_pct" class="form-input w-full pr-8" min="0" max="100" step="0.1" placeholder="Default rate">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Eligible Vendor Tiers</label>
                            <select name="eligible_vendor_tiers[]" class="form-select w-full" data-select2 multiple>
                                <option value="bronze">Bronze</option>
                                <option value="silver">Silver</option>
                                <option value="gold">Gold</option>
                                <option value="platinum">Platinum</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Eligible Categories</label>
                            <select name="eligible_categories[]" class="form-select w-full" data-select2 multiple>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name_en }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-card>

            </div>

            {{-- ─── Sidebar ──────────────────────────────────────────────────── --}}
            <div class="w-72 flex-shrink-0 space-y-4 sticky top-20">
                <x-card title="Settings">
                    <div class="space-y-3">
                        <x-form-toggle name="is_featured" label="Featured Sale" :value="false" />
                        <x-form-toggle name="is_exclusive" label="Exclusive (Invite-Only)" :value="false" />
                        <x-form-toggle name="price_drop_required" label="Require Price Drop" :value="true" />
                    </div>
                </x-card>

                <x-card>
                    <div class="space-y-2">
                        <button type="submit" class="btn btn-primary w-full justify-center">
                            Create Flash Sale
                        </button>
                        <a href="{{ route('admin.flash-sales.index') }}" class="btn btn-ghost w-full justify-center">
                            Cancel
                        </a>
                    </div>
                </x-card>
            </div>

        </div>
    </form>
@endsection
