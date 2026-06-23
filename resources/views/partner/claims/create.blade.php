@extends('layouts.partner')

@section('title', 'Submit a Claim')

@section('content')

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('partner.claims.index') }}" class="text-gray-400 hover:text-gray-600">&larr; Claims</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900">Submit a Claim</h1>
    </div>

    <div class="max-w-2xl">
        <div class="card p-6">
            <form method="POST" action="{{ route('partner.claims.store') }}">
                @csrf

                <div class="space-y-5">

                    <div>
                        <label class="label" for="shipment_id">Shipment</label>
                        <select name="shipment_id" id="shipment_id" required class="input w-full @error('shipment_id') input-error @enderror">
                            <option value="">— Select a shipment —</option>
                            @foreach($shipments as $s)
                                <option value="{{ $s->id }}" @selected(old('shipment_id') === $s->id)>
                                    {{ $s->subOrder?->order?->order_number ?? $s->id }}
                                    @if($s->tracking_number) · {{ $s->tracking_number }} @endif
                                    · {{ Str::title(str_replace('_',' ',$s->status)) }}
                                </option>
                            @endforeach
                        </select>
                        @error('shipment_id')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="claim_type">Claim Type</label>
                        <select name="claim_type" id="claim_type" required class="input w-full @error('claim_type') input-error @enderror">
                            <option value="">— Select type —</option>
                            @foreach(['lost' => 'Lost Shipment', 'damaged' => 'Damaged Item', 'delayed' => 'Significant Delay', 'wrong_item' => 'Wrong Item Delivered', 'other' => 'Other'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('claim_type') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('claim_type')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="description">Description</label>
                        <textarea name="description" id="description" rows="4" required
                                  class="input w-full @error('description') input-error @enderror"
                                  placeholder="Describe the issue in detail (min. 20 characters)...">{{ old('description') }}</textarea>
                        @error('description')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="label" for="claimed_amount">Claimed Amount</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">EGP</span>
                            <input type="number" name="claimed_amount" id="claimed_amount" step="0.01" min="0.01" required
                                   class="input w-full pl-12 @error('claimed_amount') input-error @enderror"
                                   value="{{ old('claimed_amount') }}" placeholder="0.00">
                        </div>
                        @error('claimed_amount')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Evidence upload note (actual upload handled by existing file upload flow) --}}
                    <div class="bg-blue-50 rounded-lg p-4 text-sm text-blue-700">
                        <strong>Evidence:</strong> Please upload photos or documents supporting your claim
                        via the order's file upload section, then reference them in your description.
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary">Submit Claim</button>
                        <a href="{{ route('partner.claims.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>

                </div>
            </form>
        </div>
    </div>

@endsection
