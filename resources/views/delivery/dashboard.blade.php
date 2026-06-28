@extends('layouts.delivery')

@section('title', 'Home')

@section('header-right')
    <form method="POST" action="{{ route('delivery.logout') }}">
        @csrf
        <button type="submit" class="text-slate-400 text-sm">Logout</button>
    </form>
@endsection

@section('content')

@php
    /** @var \App\Models\DeliveryAgent $agent */
    $statusChipMap = [
        'active'    => 'chip-accepted',
        'on_shift'  => 'chip-picked_up',
        'suspended' => 'chip-failed',
        'inactive'  => 'chip-assigned',
    ];
@endphp

{{-- ── Availability Toggle ─────────────────────────────────────────────────── --}}
<div class="d-card mb-4"
     x-data="{
         isAvailable: {{ auth('delivery')->user()->is_available ? 'true' : 'false' }},
         loading: false,
         toggle() {
             if (this.loading) return;
             this.loading = true;
             fetch('{{ route('delivery.availability.update') }}', {
                 method: 'PUT',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                     'Accept': 'application/json',
                 },
                 body: JSON.stringify({ is_available: !this.isAvailable }),
             })
             .then(r => r.json())
             .then(d => { if (d.success) this.isAvailable = d.is_available; })
             .finally(() => this.loading = false);
         }
     }">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-base font-bold" x-text="isAvailable ? '🟢 You\'re Online' : '⚫ You\'re Offline'"></p>
            <p class="text-xs text-slate-400 mt-0.5" x-text="isAvailable ? 'Ready to receive assignments' : 'Not receiving assignments'"></p>
        </div>
        <button type="button" @click="toggle()" :disabled="loading"
            class="relative focus:outline-none" style="width:64px; height:34px;">
            <div class="toggle-track" :class="{ 'on': isAvailable }">
                <div class="toggle-thumb"></div>
            </div>
        </button>
    </div>
</div>

{{-- ── Today Stats ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-3 gap-3 mb-4">
    @foreach([
        ['label' => 'Total',     'value' => $todayAssignments->total     ?? 0, 'color' => 'text-slate-300'],
        ['label' => 'Completed', 'value' => $todayAssignments->completed ?? 0, 'color' => 'text-green-400'],
        ['label' => 'Earnings',  'value' => number_format(($earningsToday ?? 0) / 100, 2), 'color' => 'text-yellow-400'],
    ] as $s)
        <div class="d-card text-center">
            <p class="text-xl font-bold {{ $s['color'] }}">{{ $s['value'] }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ $s['label'] }}</p>
        </div>
    @endforeach
</div>

{{-- ── Pending Assignments ──────────────────────────────────────────────────── --}}
@if($pendingAssignments->isNotEmpty())
    <div class="mb-5">
        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Pending Actions</h2>
        <div class="space-y-3">
            @foreach($pendingAssignments as $a)
                @php
                    $chipClass = 'chip-' . $a->status;
                    $actionLabel = match($a->status) {
                        'assigned'  => 'Accept',
                        'accepted'  => 'Mark Picked Up',
                        'picked_up' => 'Deliver',
                        default     => 'View',
                    };
                    $actionColor = match($a->status) {
                        'picked_up' => 'btn-yellow',
                        'accepted'  => 'btn-blue',
                        default     => 'btn-outline',
                    };
                @endphp
                <a href="{{ route('delivery.assignments.show', $a->id) }}" class="block d-card hover:bg-slate-700/60 transition-colors">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-semibold text-sm">#{{ $a->subOrder?->sub_order_number ?? $a->id }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $a->subOrder?->items?->count() ?? 0 }} items
                                @if($a->assigned_at)
                                    · {{ $a->assigned_at->format('H:i') }}
                                @endif
                            </p>
                        </div>
                        <span class="chip {{ $chipClass }}">{{ ucfirst(str_replace('_', ' ', $a->status)) }}</span>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <span class="flex-1 btn-action {{ $actionColor }} text-sm min-h-[46px]">{{ $actionLabel }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@else
    <div class="d-card text-center py-10 mb-4">
        <div class="text-4xl mb-3">📦</div>
        <p class="font-semibold text-slate-300">No pending assignments</p>
        <p class="text-sm text-slate-500 mt-1">Check back soon!</p>
    </div>
@endif

{{-- ── Quick Actions ────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 gap-3 mb-6">
    <a href="{{ route('delivery.assignments.index') }}"
       class="d-card text-center py-5 hover:bg-slate-700/60 transition-colors">
        <div class="text-2xl mb-1">📦</div>
        <p class="text-sm font-semibold">All Orders</p>
        <p class="text-xs text-slate-400 mt-0.5">Today's list</p>
    </a>
    <a href="{{ route('delivery.earnings.index') }}"
       class="d-card text-center py-5 hover:bg-slate-700/60 transition-colors">
        <div class="text-2xl mb-1">💰</div>
        <p class="text-sm font-semibold">My Earnings</p>
        <p class="text-xs text-slate-400 mt-0.5">Payouts &amp; history</p>
    </a>
</div>

@endsection

@push('scripts')
<script>
// Start background location tracking
@if(auth('delivery')->user()->status === 'on_shift' || auth('delivery')->user()->is_available)
    window.DeliveryLocation.start(@json(route('delivery.location.update')));
@endif
</script>
@endpush
