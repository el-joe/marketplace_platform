@extends('layouts.carrier')

@section('title', __('carrier.agents.title'))

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-black text-gray-900">{{ __('carrier.agents.title') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('carrier.agents.subtitle') }}</p>
    </div>
    @if(auth('shipping_supervisor')->user()->hasPermission('manage_agents'))
    <a href="{{ route('carrier.agents.create') }}"
       class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-4 py-2 rounded-lg transition">
        + {{ __('carrier.agents.add_agent') }}
    </a>
    @endif
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    @if($agents->isEmpty())
    <div class="py-16 text-center text-gray-400 text-sm">{{ __('carrier.agents.no_agents') }}</div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-6 py-3 text-right font-semibold">{{ __('carrier.agents.name') }}</th>
                    <th class="px-6 py-3 text-right font-semibold">{{ __('carrier.common.phone') }}</th>
                    <th class="px-6 py-3 text-right font-semibold">{{ __('carrier.agents.vehicle_type') }}</th>
                    <th class="px-6 py-3 text-right font-semibold">{{ __('carrier.assignments.status') }}</th>
                    <th class="px-6 py-3 text-right font-semibold">{{ __('carrier.agents.avg_rating') }}</th>
                    <th class="px-6 py-3 text-right font-semibold">{{ __('carrier.common.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($agents as $agent)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <div class="font-medium text-gray-900">{{ $agent->name }}</div>
                        <div class="text-xs text-gray-400">{{ $agent->email }}</div>
                    </td>
                    <td class="px-6 py-3 text-gray-600">{{ $agent->phone }}</td>
                    <td class="px-6 py-3 text-gray-600">{{ __('carrier.vehicle_types.'.$agent->vehicle_type, [], 'ar') ?? $agent->vehicle_type }}</td>
                    <td class="px-6 py-3">
                        @php
                            $sc = ['active'=>'emerald','suspended'=>'red','inactive'=>'gray','on_shift'=>'blue'][$agent->status] ?? 'gray';
                            $sl = [
                                'active'    => __('carrier.agents.active'),
                                'suspended' => __('carrier.agents.suspended'),
                                'inactive'  => __('carrier.agents.inactive'),
                                'on_shift'  => __('carrier.agents.on_shift'),
                            ][$agent->status] ?? $agent->status;
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $sc }}-100 text-{{ $sc }}-700">{{ $sl }}</span>
                    </td>
                    <td class="px-6 py-3 text-gray-600">{{ number_format($agent->rating_avg, 1) }} ⭐</td>
                    <td class="px-6 py-3">
                        @if(auth('shipping_supervisor')->user()->hasPermission('manage_agents'))
                        @if($agent->status === 'suspended')
                        <form method="POST" action="{{ route('carrier.agents.activate', $agent->id) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-emerald-600 hover:underline text-xs font-medium">{{ __('carrier.agents.activate') }}</button>
                        </form>
                        @else
                        <form method="POST" action="{{ route('carrier.agents.suspend', $agent->id) }}" class="inline">
                            @csrf @method('PATCH')
                            <button class="text-red-500 hover:underline text-xs font-medium">{{ __('carrier.agents.suspend') }}</button>
                        </form>
                        @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $agents->links() }}
    </div>
    @endif
</div>

@endsection
