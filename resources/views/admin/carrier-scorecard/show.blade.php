@extends('layouts.admin')

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
@endpush

@section('title', $shippingCompany->name . ' — Scorecard')

@section('content')

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.carrier-scorecard.index') }}" class="text-gray-400 hover:text-gray-600">&larr; Scorecards</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-xl font-bold text-gray-900">{{ $shippingCompany->name }}</h1>
    </div>

    {{-- Period switcher --}}
    <form method="GET" class="flex gap-2 items-center mb-6">
        <label class="text-sm text-gray-600">Period:</label>
        <select name="period" onchange="this.form.submit()" class="input-sm">
            @foreach(['week' => 'This Week', 'month' => 'This Month', 'quarter' => 'This Quarter', 'year' => 'This Year'] as $val => $label)
                <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </form>

    {{-- ─── KPI Cards ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <x-stat-card title="Avg Rating" :value="$scorecard['avg_rating'] ? $scorecard['avg_rating'] . ' ★' : '—'"
            iconBg="bg-yellow-100 text-yellow-600" />
        <x-stat-card title="On-Time %" :value="$scorecard['on_time_pct'] !== null ? $scorecard['on_time_pct'] . '%' : '—'"
            iconBg="bg-green-100 text-green-600" />
        <x-stat-card title="Claims" :value="$scorecard['total_claims']"
            iconBg="bg-orange-100 text-orange-600" />
        <x-stat-card title="Total Compensated" :value="number_format($scorecard['total_compensated'] / 100, 2)"
            iconBg="bg-red-100 text-red-600" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ─── Rating trend chart ───────────────────────────────────────── --}}
        <div class="card p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-4">Rating Trend (6 months)</h2>
            <canvas id="ratingChart" height="180"></canvas>
        </div>

        {{-- ─── Recent ratings ───────────────────────────────────────────── --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Recent Ratings</h2>
            </div>
            <ul class="divide-y divide-gray-100 text-sm max-h-72 overflow-y-auto">
                @forelse($recentRatings as $r)
                    <li class="px-5 py-3 flex items-start justify-between gap-3">
                        <div>
                            <span class="font-medium capitalize">{{ $r->rated_by_type }}</span>
                            @if($r->comment)
                                <p class="text-gray-500 text-xs mt-0.5 line-clamp-2">{{ $r->comment }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <span class="font-semibold">{{ $r->rating }}</span>
                            <span class="text-yellow-400 text-xs">★</span>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-gray-400">No ratings yet.</li>
                @endforelse
            </ul>
        </div>

    </div>

    <script>
        const trendData = @json($trend);
        new Chart(document.getElementById('ratingChart'), {
            type: 'line',
            data: {
                labels: trendData.map(r => r.month),
                datasets: [{
                    label: 'Avg Rating',
                    data: trendData.map(r => r.avg_rating),
                    tension: 0.35,
                    fill: true,
                    backgroundColor: 'rgba(99,102,241,0.1)',
                    borderColor: 'rgb(99,102,241)',
                    pointRadius: 4,
                }]
            },
            options: {
                scales: {
                    y: { min: 1, max: 5, ticks: { stepSize: 1 } }
                },
                plugins: { legend: { display: false } }
            }
        });
    </script>

@endsection
