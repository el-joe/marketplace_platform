@extends('layouts.delivery')

@section('title', 'My Orders')

@section('content')

    <h1 class="text-lg font-bold mb-4">Today's Orders</h1>

    {{-- ── Pending / Active ─────────────────────────────────────────────────────── --}}
    @if($pending->isNotEmpty())
        <h2 class="text-xs font-bold text-yellow-400 uppercase tracking-wider mb-2">Needs Action ({{ $pending->count() }})</h2>
        <div class="space-y-3 mb-5">
            @foreach($pending as $a)
                @include('delivery.assignments._card', ['assignment' => $a])
            @endforeach
        </div>
    @endif

    {{-- ── Completed ────────────────────────────────────────────────────────────── --}}
    @if($completed->isNotEmpty())
        <h2 class="text-xs font-bold text-green-400 uppercase tracking-wider mb-2">Delivered ({{ $completed->count() }})</h2>
        <div class="space-y-3 mb-5">
            @foreach($completed as $a)
                @include('delivery.assignments._card', ['assignment' => $a])
            @endforeach
        </div>
    @endif

    {{-- ── Failed ───────────────────────────────────────────────────────────────── --}}
    @if($failed->isNotEmpty())
        <h2 class="text-xs font-bold text-red-400 uppercase tracking-wider mb-2">Failed ({{ $failed->count() }})</h2>
        <div class="space-y-3 mb-5">
            @foreach($failed as $a)
                @include('delivery.assignments._card', ['assignment' => $a])
            @endforeach
        </div>
    @endif

    @if($pending->isEmpty() && $completed->isEmpty() && $failed->isEmpty())
        <div class="d-card text-center py-14">
            <div class="text-5xl mb-3">📭</div>
            <p class="font-semibold text-slate-300">No assignments today</p>
            <p class="text-sm text-slate-500 mt-1">You're all caught up!</p>
        </div>
    @endif

@endsection
