@extends('layouts.admin')

@section('title', 'Claim ' . $claim->claim_number)

@section('content')

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.carrier-claims.index') }}" class="text-gray-400 hover:text-gray-600">
            &larr; Claims
        </a>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900">{{ $claim->claim_number }}</h1>
        <span class="badge {{ $claim->statusBadgeClass() }}">
            {{ Str::title(str_replace('_',' ',$claim->status)) }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ─── Main detail ──────────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            <div class="card p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Claim Details</h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">Type</dt>
                        <dd class="font-medium">{{ Str::title(str_replace('_',' ',$claim->claim_type)) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Claimed Amount</dt>
                        <dd class="font-medium">{{ $claim->claimed_amount_formatted }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Carrier</dt>
                        <dd class="font-medium">{{ $claim->shippingCompany?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Agent</dt>
                        <dd class="font-medium">{{ $claim->deliveryAgent?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Vendor</dt>
                        <dd>{{ $claim->shipment?->subOrder?->vendor?->store_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Submitted</dt>
                        <dd>{{ $claim->created_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Description</p>
                    <p class="text-gray-800 text-sm leading-relaxed">{{ $claim->description }}</p>
                </div>
            </div>

            {{-- Evidence Files --}}
            @if($claim->evidence_files)
                <div class="card p-5">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Evidence</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($claim->evidence_files as $file)
                            <a href="{{ Storage::url($file) }}" target="_blank"
                               class="block rounded-lg overflow-hidden border border-gray-200 hover:border-primary-400 transition">
                                @if(Str::endsWith($file, ['.jpg','.jpeg','.png','.webp']))
                                    <img src="{{ Storage::url($file) }}" class="w-full h-28 object-cover" alt="Evidence">
                                @else
                                    <div class="h-28 flex items-center justify-center bg-gray-50 text-gray-400 text-xs">
                                        {{ basename($file) }}
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Resolution --}}
            @if($claim->isResolved())
                <div class="card p-5 border-l-4 {{ $claim->status === 'rejected' ? 'border-red-400' : 'border-green-400' }}">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">Resolution</h2>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Decision</dt>
                            <dd class="font-medium capitalize">{{ $claim->status }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Compensated Amount</dt>
                            <dd class="font-medium">{{ $claim->compensated_amount_formatted }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Resolved by</dt>
                            <dd>{{ $claim->resolvedBy?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Resolved at</dt>
                            <dd>{{ $claim->resolved_at?->format('d M Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>
                    @if($claim->resolution_notes)
                        <p class="mt-3 text-sm text-gray-700 leading-relaxed">{{ $claim->resolution_notes }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- ─── Actions ──────────────────────────────────────────────────── --}}
        <div class="space-y-4">

            @if(! $claim->isResolved())

                @if($claim->status === 'submitted')
                    <form method="POST" action="{{ route('admin.carrier-claims.under-review', $claim) }}">
                        @csrf @method('PATCH')
                        <button class="w-full btn btn-secondary">Mark Under Review</button>
                    </form>
                @endif

                <div class="card p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Resolve Claim</h3>
                    <form method="POST" action="{{ route('admin.carrier-claims.resolve', $claim) }}">
                        @csrf @method('PATCH')

                        <div class="mb-3">
                            <label class="label">Decision</label>
                            <select name="decision" required class="input w-full">
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="label">Compensation Amount</label>
                            <input type="number" name="compensated_amount" step="0.01" min="0"
                                   placeholder="0.00" class="input w-full"
                                   value="{{ old('compensated_amount') }}">
                            <p class="text-xs text-gray-500 mt-1">Leave blank if rejecting or no compensation.</p>
                        </div>

                        <div class="mb-4">
                            <label class="label">Resolution Notes</label>
                            <textarea name="resolution_notes" rows="3" class="input w-full"
                                      placeholder="Internal notes on the decision...">{{ old('resolution_notes') }}</textarea>
                        </div>

                        <button type="submit" class="w-full btn btn-primary">Submit Decision</button>
                    </form>
                </div>

            @endif

        </div>

    </div>

@endsection
