@extends('layouts.partner')

@section('title', 'Exceptional Shipping Zones')

@section('content')

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Exceptional Shipping Zones</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Alert the admin when your carrier charges more than the standard customer rate for specific cities.
                Admin will configure a cost-split subsidy if approved.
            </p>
        </div>
        <button type="button" onclick="openAlertModal()"
                class="shrink-0 px-4 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 font-medium">
            Alert Admin
        </button>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if($myAlerts->isNotEmpty())
        <div class="mt-4">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Alert History</h2>
            <x-card padding="none">
                <table class="table-base w-full">
                    <thead>
                        <tr>
                            <th>Warehouse</th>
                            <th>Cities</th>
                            <th>Carrier</th>
                            <th>Reported Fee</th>
                            <th>Status</th>
                            <th>Admin Note</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($myAlerts as $alert)
                            <tr>
                                <td>{{ $alert->warehouse->name ?? '—' }}</td>
                                <td class="text-gray-500 text-xs">{{ count($alert->city_ids ?? []) }} {{ Str::plural('city', count($alert->city_ids ?? [])) }}</td>
                                <td class="text-gray-500">{{ $alert->carrier->name ?? 'All carriers' }}</td>
                                <td>{{ $alert->reported_carrier_fee }} {{ $alert->currency }}</td>
                                <td>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $alert->status === 'pending'  ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $alert->status === 'accepted' ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $alert->status === 'rejected' ? 'bg-gray-100 text-gray-500' : '' }}">
                                        {{ ucfirst($alert->status) }}
                                    </span>
                                    @if($alert->isPending())
                                        <form method="POST" action="{{ route('partner.exceptional-zone-alerts.cancel', $alert) }}" class="inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Cancel this alert?')"
                                                    class="ml-1 text-xs text-red-500 hover:underline">
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-gray-500 text-xs">{{ $alert->admin_note ?? '—' }}</td>
                                <td class="text-gray-400 text-xs">{{ $alert->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-4 py-3">{{ $myAlerts->links() }}</div>
            </x-card>
        </div>
    @else
        <div class="text-center py-12 text-gray-400">
            <p class="text-lg mb-1">No alerts yet</p>
            <p class="text-sm">Use "Alert Admin" above if a carrier is charging you more than the customer rate for some cities.</p>
        </div>
    @endif

    <div id="alertModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between mb-4">
                <h3 class="font-bold text-lg">Alert Admin: Exceptional Zone</h3>
                <button type="button" onclick="closeAlertModal()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>

            <form method="POST" action="{{ route('partner.exceptional-zone-alerts.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700">
                        Warehouse <span class="text-red-500">*</span>
                    </label>
                    <select name="warehouse_id" id="alertWarehouseId" required
                            class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
                            onchange="loadCitiesForWarehouse(this.value)">
                        <option value="">Select a warehouse&hellip;</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700">
                        Cities <span class="text-red-500">*</span>
                    </label>
                    <div id="alertCitiesList" class="mt-1 max-h-48 overflow-y-auto border rounded-lg p-2 text-sm text-gray-400">
                        Select a warehouse first.
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700">
                        Carrier <span class="text-gray-400 font-normal">(leave empty = applies to all carriers)</span>
                    </label>
                    <select name="carrier_id" id="alertCarrierId" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">All carriers</option>
                        @foreach($carriers as $carrier)
                            <option value="{{ $carrier->id }}">{{ $carrier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="text-sm font-medium text-gray-700">
                        Carrier Fee You Pay <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2 mt-1">
                        <input type="number" name="reported_carrier_fee" required min="1"
                               class="flex-1 border rounded-lg px-3 py-2 text-sm" placeholder="e.g. 3000 for 3 OMR">
                        <select name="currency" required class="w-28 border rounded-lg px-3 py-2 text-sm">
                            @foreach(['SAR','AED','OMR','KWD','QAR','BHD','EGP','JOD'] as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        Enter the BIGINT value — base currency units, no decimals (e.g. 3000 = 3 OMR if currency is OMR).
                    </p>
                </div>

                <div class="mb-5">
                    <label class="text-sm font-medium text-gray-700">
                        Note to Admin <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea name="vendor_note" rows="3" maxlength="1000"
                              class="mt-1 w-full border rounded-lg px-3 py-2 text-sm resize-none"
                              placeholder="Expected monthly orders to these cities, why it matters to you, etc."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeAlertModal()"
                            class="flex-1 border rounded-lg py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 bg-orange-500 text-white rounded-lg py-2 text-sm font-medium hover:bg-orange-600">
                        Submit Alert
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="module">
        const citiesUrl = @json(route('partner.exceptional-zone-alerts.cities-for-warehouse'));

        window.openAlertModal = function () {
            const modal = document.getElementById('alertModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        window.closeAlertModal = function () {
            const modal = document.getElementById('alertModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        window.loadCitiesForWarehouse = async function (warehouseId) {
            const list = document.getElementById('alertCitiesList');
            if (!warehouseId) {
                list.innerHTML = 'Select a warehouse first.';
                return;
            }

            list.textContent = 'Loading cities…';

            const res = await fetch(citiesUrl + '?warehouse_id=' + encodeURIComponent(warehouseId), {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!data.cities || data.cities.length === 0) {
                list.innerHTML = '<p class="text-gray-400">No active cities found for this warehouse\'s country.</p>';
                return;
            }

            list.innerHTML = data.cities.map(function (city) {
                const label = city.name_en + (city.has_zone ? '' : ' (no zone assigned)');
                return '<label class="flex items-center gap-2 py-1 text-sm">'
                    + '<input type="checkbox" name="city_ids[]" value="' + city.id + '">'
                    + '<span>' + label + '</span>'
                    + '</label>';
            }).join('');
        };
    </script>
@endpush
