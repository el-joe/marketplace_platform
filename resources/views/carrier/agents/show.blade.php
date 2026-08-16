@extends('layouts.carrier')

@section('title', $agent->name)

@section('content')

<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <a href="{{ route('carrier.agents.index') }}"
           class="text-indigo-600 hover:underline text-sm">← {{ __('carrier.agents.back') }}</a>
        <h1 class="text-2xl font-black text-gray-900 mt-2">{{ $agent->name }}</h1>
        <p class="text-sm text-gray-500">{{ $agent->email }} · {{ $agent->phone }}</p>
    </div>
    <div>
        @php $sc = ['active'=>'emerald','on_shift'=>'blue','inactive'=>'gray','suspended'=>'red'][$agent->status->value] ?? 'gray'; @endphp
        <span class="px-3 py-1 rounded-full text-xs font-bold bg-{{ $sc }}-100 text-{{ $sc }}-700 capitalize">
            {{ $agent->status->value }}
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Agent info --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-3 text-sm">
        <h2 class="font-bold text-gray-900 mb-4">{{ __('carrier.agents.details') }}</h2>
        <div class="flex justify-between"><span class="text-gray-500">{{ __('carrier.agents.vehicle_type') }}</span><span class="font-medium capitalize">{{ $agent->vehicle_type->value }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">{{ __('carrier.agents.current_zone') }}</span><span class="font-medium">{{ $agent->zone?->name ?? '—' }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">{{ __('carrier.agents.rating') }}</span><span class="font-medium">{{ number_format($agent->rating_avg, 1) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">{{ __('carrier.agents.total_deliveries') }}</span><span class="font-medium">{{ $agent->total_deliveries }}</span></div>
    </div>

    {{-- Zone assignment --}}
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-bold text-gray-900 mb-4">{{ __('carrier.agents.zone_assignment') }}</h2>

        <form id="zone-assign-form" class="flex gap-3 items-end flex-wrap">
            @csrf
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-semibold text-gray-500 mb-1.5">{{ __('carrier.agents.zone') }}</label>
                <select name="zone_id" id="zone-select"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">— {{ __('carrier.agents.no_zone') }} —</option>
                    @foreach($zones as $zone)
                        @php $full = $zone->max_active_agents && $zone->agents_count >= $zone->max_active_agents && $zone->id !== $agent->zone_id; @endphp
                        <option value="{{ $zone->id }}"
                                {{ $agent->zone_id === $zone->id ? 'selected' : '' }}
                                {{ $full ? 'disabled' : '' }}>
                            {{ $zone->name }}
                            @if($zone->max_active_agents)
                                ({{ $zone->agents_count }}/{{ $zone->max_active_agents }}){{ $full ? ' — Full' : '' }}
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" id="zone-submit-btn"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2.5 rounded-lg text-sm transition">
                {{ __('carrier.agents.update_zone') }}
            </button>
        </form>

        <div id="zone-result" class="mt-3 text-sm hidden"></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('zone-assign-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('zone-submit-btn');
    btn.disabled = true; btn.textContent = '...';

    const res  = await fetch('{{ route('carrier.agents.assign-zone', $agent->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            _method: 'PATCH',
            zone_id: document.getElementById('zone-select').value || null,
        }),
    });

    const data = await res.json();
    const resultEl = document.getElementById('zone-result');
    resultEl.classList.remove('hidden');

    if (data.success) {
        resultEl.className = 'mt-3 text-sm text-emerald-600 font-medium';
        resultEl.textContent = data.message;
        setTimeout(() => location.reload(), 1000);
    } else {
        resultEl.className = 'mt-3 text-sm text-red-600 font-medium';
        resultEl.textContent = data.message;
        btn.disabled = false;
        btn.textContent = '{{ __('carrier.agents.update_zone') }}';
    }
});
</script>
@endpush
