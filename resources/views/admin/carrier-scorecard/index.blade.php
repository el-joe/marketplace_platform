@extends('layouts.admin')

@section('title', __('admin.carriers_section.scorecard_title'))

@section('content')

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('admin.carriers_section.scorecard_title') }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.carriers_section.scorecard_desc') }}</p>
        </div>
        <form method="GET" class="flex gap-2 items-center">
            <label class="text-sm text-gray-600">{{ __('admin.carriers_section.period_label') }}</label>
            <select name="period" onchange="this.form.submit()" class="input-sm">
                @foreach(['week' => __('admin.carriers_section.this_week'), 'month' => __('admin.carriers_section.this_month'), 'quarter' => __('admin.carriers_section.this_quarter'), 'year' => __('admin.carriers_section.this_year')] as $val => $label)
                    <option value="{{ $val }}" @selected($period === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="card overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="th">{{ __('admin.carriers_section.rank_col') }}</th>
                    <th class="th">{{ __('admin.carriers_section.carrier_col') }}</th>
                    <th class="th">{{ __('admin.carriers_section.avg_rating_col') }}</th>
                    <th class="th">{{ __('admin.carriers_section.on_time_pct_col') }}</th>
                    <th class="th">{{ __('admin.carriers_section.claims_col') }}</th>
                    <th class="th">{{ __('admin.carriers_section.approved_pct_col') }}</th>
                    <th class="th">{{ __('admin.carriers_section.compensated_col') }}</th>
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
                        <td class="td text-end">
                            <a href="{{ route('admin.carrier-scorecard.show', $company) }}"
                               class="text-primary-600 hover:underline text-xs font-medium">{{ __('admin.carriers_section.details_link') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="td text-center text-gray-400 py-10">{{ __('admin.carriers_section.no_active_carriers') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
