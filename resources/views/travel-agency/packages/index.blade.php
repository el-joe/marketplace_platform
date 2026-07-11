@extends('layouts.travel-agency')

@section('title', __('travel.packages.title'))

@section('content')
    <div class="space-y-5">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black text-gray-900">{{ __('travel.packages.title') }}</h1>
            <a href="{{ route('travel-agency.packages.create') }}"
                class="px-5 py-2.5 bg-blue-500 text-white rounded-xl font-bold hover:bg-blue-400 transition-colors">
                + {{ __('travel.packages.create_package') }}
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">{{ __('travel.packages.title') }}</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">{{ __('travel.packages.destination_country') }}</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">{{ __('travel.packages.departure_date') }}</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">{{ __('travel.packages.price_per_person') }}</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">{{ __('travel.packages.available_seats') }}</th>
                        <th class="px-4 py-3 text-start font-semibold text-gray-700">{{ __('travel.packages.status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($packages as $pkg)
                        @php
                            $colors = ['draft' => 'bg-gray-100 text-gray-600', 'pending_review' => 'bg-amber-100 text-amber-700', 'active' => 'bg-emerald-100 text-emerald-700', 'sold_out' => 'bg-purple-100 text-purple-700', 'expired' => 'bg-gray-100 text-gray-500'];
                            $labels = ['draft' => __('travel.packages.status_draft'), 'pending_review' => __('travel.packages.status_pending_review'), 'active' => __('travel.packages.status_active'), 'sold_out' => __('travel.packages.status_sold_out'), 'expired' => __('travel.packages.status_expired')];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $pkg->title_ar ?: $pkg->title_en }}</p>
                                <p class="text-xs text-gray-400">{{ $pkg->title_en }}</p>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if($pkg->destinationCountry)
                                    {{ $pkg->destinationCountry->flag_emoji }}
                                    {{ $pkg->destinationCountry->name_en }}{{ $pkg->destinationCity ? ', ' . $pkg->destinationCity->name_en : '' }}
                                @else
                                    {{ $pkg->destination_country }}{{ $pkg->destination_city ? ', ' . $pkg->destination_city : '' }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $pkg->departure_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $pkg->priceFormatted() }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $pkg->seats_booked }} / {{ $pkg->available_seats ?? '∞' }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$pkg->status->value] ?? '' }}">
                                    {{ $labels[$pkg->status->value] ?? $pkg->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 flex gap-2">
                                <a href="{{ route('travel-agency.packages.show', $pkg) }}"
                                    class="text-blue-600 text-xs hover:underline">{{ __('travel.packages.view') }}</a>
                                @if(in_array($pkg->status, [\App\Enums\TravelPackageStatus::Draft, \App\Enums\TravelPackageStatus::PendingReview]))
                                    <a href="{{ route('travel-agency.packages.edit', $pkg) }}"
                                        class="text-amber-600 text-xs hover:underline">{{ __('travel.packages.edit') }}</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-400 text-sm">
                                {{ __('travel.packages.no_packages') }}
                                <a href="{{ route('travel-agency.packages.create') }}" class="text-blue-600 hover:underline">{{ __('travel.packages.add_first_package') }}</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $packages->links() }}
            </div>
        </div>
    </div>
@endsection
