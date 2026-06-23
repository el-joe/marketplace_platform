@extends('layouts.admin')

@section('title', 'Carrier Scorecard')

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Carrier Scorecard</h1>
            <p class="text-sm text-gray-500 mt-0.5">Ranked performance across all active shipping companies.</p>
        </div>
        <form method="GET" class="flex gap-2 items-center">
            <label class="text-sm text-gray-600">Period:</label>
            <select name="period" onchange="this.form.submit()" class="input-sm">
                @foreach(['week' => 'This Week', 'month' => 'This Month', 'quarter' => 'This Quarter', 'year' => 'This Year'] as $val => $label)
                    <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">#</th>
                    <th class="th">Carrier</th>
                    <th class="th">Avg Rating</th>
                    <th class="th">On-Time %</th>
                    <th class="th">Claims</th>
                    <th class="th">Approved %</th>
                    <th class="th">Compensated</th>
                    <th class="th"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($scorecards as $i => $row)
                    @php $sc = $row['scorecard']; $company = $row['company']; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="td text-gray-400 font-semibold">{{ $i + 1 }}</td>
                        <td class="td font-medium text-gray-900">{{ $company->name }}</td>
                        <td class="td">
                            @if($sc['avg_rating'])
                                <div class="flex items-center gap-1">
                                    <span class="font-semibold">{{ $sc['avg_rating'] }}</span>
                                    <span class="text-yellow-400">★</span>
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="td">
                            @if($sc['on_time_pct'] !== null)
                                <span class="{{ $sc['on_time_pct'] >= 85 ? 'text-green-600' : ($sc['on_time_pct'] >= 60 ? 'text-yellow-600' : 'text-red-600') }} font-medium">
                                    {{ $sc['on_time_pct'] }}%
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="td">{{ $sc['total_claims'] }}</td>
                        <td class="td">
                            @if($sc['claims_approved_pct'] !== null)
                                {{ $sc['claims_approved_pct'] }}%
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="td">{{ number_format($sc['total_compensated'] / 100, 2) }}</td>
                        <td class="td text-right">
                            <a href="{{ route('admin.carrier-scorecard.show', $company) }}"
                               class="text-primary-600 hover:underline text-xs font-medium">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="td text-center text-gray-400 py-10">No active carriers.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
