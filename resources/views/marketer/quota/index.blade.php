@extends('layouts.marketer')

@section('title', __('marketer.quota.title'))
@section('page-title', __('marketer.quota.title'))

@section('content')

    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-800">{{ __('marketer.quota.title') }}</h2>
        <p class="text-sm text-gray-500">{{ __('marketer.quota.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @php
                $barColors = [
                    'green' => 'bg-green-500',
                    'yellow' => 'bg-yellow-400',
                    'red' => 'bg-red-500',
                ];
                $badgeColors = [
                    'green' => 'bg-green-100 text-green-700',
                    'yellow' => 'bg-yellow-100 text-yellow-700',
                    'red' => 'bg-red-100 text-red-700',
                ];
            @endphp
            @foreach($categories as $row)
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="font-bold text-gray-800 text-sm">{{ $row['label'] }}</p>
                        <span class="text-xs font-semibold rounded-full px-2.5 py-0.5 {{ $badgeColors[$row['color']] }}">
                            {{ __('marketer.quota.completed_of_target', ['completed' => $row['completed'], 'target' => $row['target']]) }}
                        </span>
                    </div>
                    <div class="w-full h-2.5 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-full rounded-full {{ $barColors[$row['color']] }}" style="width: {{ $row['percent'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-5">
            <h3 class="font-bold text-gray-800 mb-3 text-sm">{{ __('marketer.quota.title') }}</h3>
            <canvas id="quota-donut-chart" height="220"></canvas>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('quota-donut-chart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: @json($categories->pluck('label')),
            datasets: [{
                data: @json($categories->pluck('completed')),
                backgroundColor: ['#facc15', '#22c55e', '#3b82f6', '#f97316'],
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } }
        }
    });
</script>
@endpush
