@extends('layouts.admin')

@section('title', 'Delivery Zones')

@section('content')

    {{-- ─── Header ──────────────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Delivery Zones</h1>
            <p class="text-sm text-gray-500 mt-0.5">Define geographic zones and assign agents.</p>
        </div>
        <button type="button" id="add-zone-btn" class="btn btn-primary btn-sm">+ Add Zone</button>
    </div>

    {{-- ─── Zone Cards ──────────────────────────────────────────────────────────── --}}
    @php
        $byCountry = $zones->groupBy(fn($z) => $z->country?->name_en ?? 'Unknown');
    @endphp

    @forelse($byCountry as $countryName => $countryZones)
        <div class="mb-8">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                <span>{{ $countryName }}</span>
                <span class="text-xs font-normal text-gray-400">({{ $countryZones->count() }} zones)</span>
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($countryZones as $zone)
                    <div class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $zone->name }}</p>
                                <code class="text-xs text-gray-400 font-mono">{{ $zone->code }}</code>
                            </div>
                            <div class="flex items-center gap-1">
                                @if($zone->is_active)
                                    <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span>
                                    <span class="text-xs text-green-600">Active</span>
                                @else
                                    <span class="inline-block w-2 h-2 rounded-full bg-gray-400"></span>
                                    <span class="text-xs text-gray-500">Inactive</span>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-1.5 text-sm mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Agents</span>
                                <span class="font-medium text-gray-800">{{ $zone->agents_count }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Delivery Fee</span>
                                <span
                                    class="font-medium text-gray-800">{{ number_format($zone->base_delivery_fee_cents / 100, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">COD Fee</span>
                                <span class="font-medium text-gray-800">{{ number_format($zone->cod_fee_cents / 100, 2) }}</span>
                            </div>
                            @if($zone->max_active_agents)
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Max Agents</span>
                                    <span class="font-medium text-gray-800">{{ $zone->max_active_agents }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="flex gap-2">
                            <button type="button" class="edit-zone-btn btn btn-xs btn-secondary flex-1" data-zone='@json($zone)'>
                                Edit
                            </button>
                            <button type="button" class="delete-zone-btn btn btn-xs btn-danger" data-zone-id="{{ $zone->id }}"
                                data-zone-name="{{ $zone->name }}">
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="text-center py-16 text-gray-400">
            <p class="text-lg font-medium">No delivery zones configured.</p>
            <p class="text-sm mt-1">Add your first zone to start assigning agents.</p>
        </div>
    @endforelse

    {{-- ─── Add / Edit Zone Modal ───────────────────────────────────────────────── --}}
    <div id="zone-modal" class="modal-backdrop hidden">
        <div class="modal-box w-full max-w-lg">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold" id="zone-modal-title">Add Zone</h3>
                <button type="button" onclick="document.getElementById('zone-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form id="zone-form" class="space-y-4">
                @csrf
                <input type="hidden" id="zone-id">
                <input type="hidden" id="zone-method" value="POST">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Country <span
                                class="text-red-500">*</span></label>
                        <select name="country_id" id="zone-country" class="form-input w-full" required>
                            <option value="">Select country</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->id }}">{{ $c->name_en }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Zone Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" id="zone-name" class="form-input w-full" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="code" id="zone-code" class="form-input w-full font-mono uppercase" required
                            placeholder="e.g. EG-CAI-1">
                        <p class="text-xs text-gray-400 mt-1">Uppercase, letters/numbers/dashes only</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Active Agents</label>
                        <input type="number" name="max_active_agents" id="zone-max-agents" class="form-input w-full"
                            min="1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Fee (cents) <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="base_delivery_fee_cents" id="zone-delivery-fee" class="form-input w-full"
                            required min="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">COD Fee (cents) <span
                                class="text-red-500">*</span></label>
                        <input type="number" name="cod_fee_cents" id="zone-cod-fee" class="form-input w-full" required
                            min="0">
                    </div>
                    <div class="col-span-2 flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="zone-active" value="1" class="rounded" checked>
                        <label for="zone-active" class="text-sm font-medium text-gray-700">Active</label>
                    </div>
                </div>

                <div class="pt-4 border-t flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('zone-modal').classList.add('hidden')"
                        class="btn btn-ghost btn-sm">Cancel</button>
                    <button type="submit" id="zone-submit-btn" class="btn btn-primary btn-sm">Create Zone</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function () {
            const STORE_URL = @json(route('admin.delivery.zones.store'));
            const BASE_URL = @json(url('admin/delivery/zones'));
            const token = () => $('meta[name=csrf-token]').attr('content');

            // ── Open Add Modal ────────────────────────────────────────────────────────
            $('#add-zone-btn').on('click', () => {
                resetForm();
                $('#zone-modal-title').text('Add Zone');
                $('#zone-submit-btn').text('Create Zone');
                $('#zone-id').val('');
                $('#zone-method').val('POST');
                document.getElementById('zone-modal').classList.remove('hidden');
            });

            // ── Open Edit Modal ───────────────────────────────────────────────────────
            $(document).on('click', '.edit-zone-btn', function () {
                const zone = $(this).data('zone');
                resetForm();
                $('#zone-modal-title').text('Edit Zone');
                $('#zone-submit-btn').text('Save Changes');
                $('#zone-id').val(zone.id);
                $('#zone-method').val('PUT');
                $('#zone-country').val(zone.country_id);
                $('#zone-name').val(zone.name);
                $('#zone-code').val(zone.code);
                $('#zone-max-agents').val(zone.max_active_agents || '');
                $('#zone-delivery-fee').val(zone.base_delivery_fee_cents);
                $('#zone-cod-fee').val(zone.cod_fee_cents);
                $('#zone-active').prop('checked', !!zone.is_active);
                document.getElementById('zone-modal').classList.remove('hidden');
            });

            function resetForm() {
                document.getElementById('zone-form').reset();
                $('#zone-active').prop('checked', true);
            }

            // ── Submit ────────────────────────────────────────────────────────────────
            $('#zone-form').on('submit', function (e) {
                e.preventDefault();
                const id = $('#zone-id').val();
                const method = id ? 'PUT' : 'POST';
                const url = id ? `${BASE_URL}/${id}` : STORE_URL;

                $.ajax({
                    url,
                    method: 'POST',
                    data: $(this).serialize() + `&_method=${method}&_token=${token()}&is_active=${$('#zone-active').is(':checked') ? 1 : 0}`,
                    success: res => {
                        if (res.success) {
                            window.Toast?.success(res.message);
                            setTimeout(() => location.reload(), 800);
                        }
                    },
                    error: xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Failed to save zone.'),
                });
            });

            // ── Delete ────────────────────────────────────────────────────────────────
            $(document).on('click', '.delete-zone-btn', function () {
                const id = $(this).data('zone-id');
                const name = $(this).data('zone-name');
                if (!confirm(`Delete zone "${name}"? This cannot be undone.`)) return;
                $.ajax({
                    url: `${BASE_URL}/${id}`,
                    method: 'POST',
                    data: { _method: 'DELETE', _token: token() },
                    success: res => {
                        if (res.success) {
                            window.Toast?.success(res.message);
                            setTimeout(() => location.reload(), 800);
                        }
                    },
                    error: xhr => window.Toast?.error(xhr.responseJSON?.message ?? 'Cannot delete zone.'),
                });
            });

            // ── Auto-uppercase code field ──────────────────────────────────────────────
            $('#zone-code').on('input', function () {
                this.value = this.value.toUpperCase();
            });
        })();
    </script>
@endpush
