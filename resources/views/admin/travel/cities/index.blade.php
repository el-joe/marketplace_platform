@extends('layouts.admin')

@section('title', 'Travel Cities')

@section('content')
<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Travel Cities</h1>
        <button onclick="openAddModal()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
            <x-heroicon name="plus" class="w-4 h-4" /> Add City
        </button>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Search city name…"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-56">
        <select name="country_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All Countries</option>
            @foreach($countries as $c)
            <option value="{{ $c->id }}" {{ request('country_id') == $c->id ? 'selected' : '' }}>
                {{ $c->flag_emoji }} {{ $c->name_en }}
            </option>
            @endforeach
        </select>
        <select name="active" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All Statuses</option>
            <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Hidden</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
        <a href="{{ route('admin.travel.cities.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">City</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Country</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Coordinates</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Packages</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($cities as $city)
                <tr class="hover:bg-gray-50" id="city-row-{{ $city->id }}">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $city->name_en }}</div>
                        <div class="text-gray-400 text-xs">{{ $city->name_ar }}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $city->country?->flag_emoji }} {{ $city->country?->name_en }}
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-500">
                        @if($city->latitude !== null)
                            {{ number_format($city->latitude, 4) }}, {{ number_format($city->longitude, 4) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($city->packages_count) }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($city->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Hidden</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end whitespace-nowrap">
                        <button onclick="openEditModal({{ json_encode($city) }})"
                                class="text-primary-600 hover:underline text-xs mr-3">Edit</button>
                        <button onclick="deleteCity('{{ $city->id }}', '{{ addslashes($city->name_en) }}')"
                                class="text-red-600 hover:underline text-xs">Delete</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">No cities found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $cities->withQueryString()->links() }}
        </div>
    </div>
</div>

{{-- Add / Edit Modal --}}
<div id="city-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 id="modal-title" class="font-semibold text-gray-900">Add City</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <x-heroicon name="x-mark" class="w-5 h-5" />
            </button>
        </div>
        <form id="city-form" class="px-6 py-4 space-y-4">
            <input type="hidden" id="city-id">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Country *</label>
                <select id="f-country" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">— Select country —</option>
                    @foreach($countries as $c)
                    <option value="{{ $c->id }}">{{ $c->flag_emoji }} {{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Name (EN) *</label>
                    <input id="f-name-en" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Dubai">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Name (AR) *</label>
                    <input id="f-name-ar" dir="rtl" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="دبي">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Latitude</label>
                    <input id="f-lat" type="number" step="any" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="25.2048">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Longitude</label>
                    <input id="f-lng" type="number" step="any" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="55.2708">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="f-active" class="rounded border-gray-300">
                <label for="f-active" class="text-sm text-gray-700">Active</label>
            </div>
            <div id="form-error" class="text-red-600 text-sm hidden"></div>
        </form>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100">
            <button onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
            <button onclick="saveCity()" id="save-btn"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
                Save
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const modal = document.getElementById('city-modal');
const errEl = document.getElementById('form-error');

function openAddModal() {
    document.getElementById('modal-title').textContent = 'Add City';
    document.getElementById('city-id').value = '';
    document.getElementById('f-country').value = '';
    ['name-en','name-ar'].forEach(k => document.getElementById('f-'+k).value = '');
    document.getElementById('f-lat').value = '';
    document.getElementById('f-lng').value = '';
    document.getElementById('f-active').checked = true;
    errEl.classList.add('hidden');
    modal.classList.remove('hidden');
}

function openEditModal(c) {
    document.getElementById('modal-title').textContent = 'Edit City';
    document.getElementById('city-id').value          = c.id;
    document.getElementById('f-country').value        = c.travel_country_id;
    document.getElementById('f-name-en').value        = c.name_en;
    document.getElementById('f-name-ar').value        = c.name_ar;
    document.getElementById('f-lat').value            = c.latitude ?? '';
    document.getElementById('f-lng').value            = c.longitude ?? '';
    document.getElementById('f-active').checked       = !!c.is_active;
    errEl.classList.add('hidden');
    modal.classList.remove('hidden');
}

function closeModal() { modal.classList.add('hidden'); }

async function saveCity() {
    const id  = document.getElementById('city-id').value;
    const url = id ? `/admin/travel/cities/${id}` : '/admin/travel/cities';
    const method = id ? 'PUT' : 'POST';

    const lat = document.getElementById('f-lat').value;
    const lng = document.getElementById('f-lng').value;

    const body = {
        travel_country_id : document.getElementById('f-country').value,
        name_en           : document.getElementById('f-name-en').value,
        name_ar           : document.getElementById('f-name-ar').value,
        latitude          : lat !== '' ? parseFloat(lat) : null,
        longitude         : lng !== '' ? parseFloat(lng) : null,
        is_active         : document.getElementById('f-active').checked,
    };

    const btn = document.getElementById('save-btn');
    btn.disabled = true;
    errEl.classList.add('hidden');

    try {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(body),
        });
        const json = await res.json();
        if (!res.ok) {
            const msg = json.errors ? Object.values(json.errors).flat().join(' ') : json.message;
            errEl.textContent = msg;
            errEl.classList.remove('hidden');
            return;
        }
        closeModal();
        window.location.reload();
    } finally {
        btn.disabled = false;
    }
}

async function deleteCity(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;

    const res = await fetch(`/admin/travel/cities/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    });
    const json = await res.json();
    if (!res.ok) { alert(json.message); return; }
    document.getElementById('city-row-' + id)?.remove();
}
</script>
@endpush
