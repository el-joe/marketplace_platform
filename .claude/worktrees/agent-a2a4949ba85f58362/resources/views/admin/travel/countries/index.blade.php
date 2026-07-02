@extends('layouts.admin')

@section('title', 'Travel Countries')

@section('content')
<div class="p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Travel Countries</h1>
        <button onclick="openAddModal()"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
            <x-heroicon name="plus" class="w-4 h-4" /> Add Country
        </button>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-3">
        <input name="q" value="{{ request('q') }}" placeholder="Search name, ISO code…"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-56">
        <select name="continent" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All Continents</option>
            @foreach($continents as $c)
            <option value="{{ $c }}" {{ request('continent') === $c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>
        <select name="active" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">All Statuses</option>
            <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Hidden</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Filter</button>
        <a href="{{ route('admin.travel.countries.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700 w-10"></th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Country</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">ISO</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Continent</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Cities</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Packages</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($countries as $country)
                <tr class="hover:bg-gray-50" id="country-row-{{ $country->id }}">
                    <td class="px-4 py-3 text-2xl">{{ $country->flag_emoji }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $country->name_en }}</div>
                        <div class="text-gray-400 text-xs">{{ $country->name_ar }}</div>
                    </td>
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">
                        {{ $country->iso_code_2 }} / {{ $country->iso_code_3 }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $country->continent ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($country->cities_count) }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ number_format($country->packages_count) }}</td>
                    <td class="px-4 py-3 text-center">
                        @if($country->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Hidden</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-end whitespace-nowrap">
                        <button onclick="openEditModal({{ json_encode($country) }})"
                                class="text-primary-600 hover:underline text-xs mr-3">Edit</button>
                        <button onclick="deleteCountry('{{ $country->id }}', '{{ addslashes($country->name_en) }}')"
                                class="text-red-600 hover:underline text-xs">Delete</button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-gray-400">No countries found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $countries->withQueryString()->links() }}
        </div>
    </div>
</div>

{{-- Add / Edit Modal --}}
<div id="country-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h2 id="modal-title" class="font-semibold text-gray-900">Add Country</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <x-heroicon name="x-mark" class="w-5 h-5" />
            </button>
        </div>
        <form id="country-form" class="px-6 py-4 space-y-4">
            <input type="hidden" id="country-id">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">ISO Code 2 *</label>
                    <input id="f-iso2" maxlength="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase" placeholder="AE">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">ISO Code 3 *</label>
                    <input id="f-iso3" maxlength="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase" placeholder="ARE">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Name (EN) *</label>
                    <input id="f-name-en" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="United Arab Emirates">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Name (AR) *</label>
                    <input id="f-name-ar" dir="rtl" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="الإمارات العربية المتحدة">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Flag Emoji</label>
                    <input id="f-flag" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="🇦🇪">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Continent</label>
                    <select id="f-continent" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">— Select —</option>
                        @foreach(['Africa','Americas','Asia','Europe','Oceania'] as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" id="f-active" class="rounded border-gray-300">
                <label for="f-active" class="text-sm text-gray-700">Active (visible to travel agencies)</label>
            </div>
            <div id="form-error" class="text-red-600 text-sm hidden"></div>
        </form>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100">
            <button onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
            <button onclick="saveCountry()" id="save-btn"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700">
                Save
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const modal  = document.getElementById('country-modal');
const errEl  = document.getElementById('form-error');

function openAddModal() {
    document.getElementById('modal-title').textContent = 'Add Country';
    document.getElementById('country-id').value = '';
    ['iso2','iso3','name-en','name-ar','flag'].forEach(k => document.getElementById('f-'+k).value = '');
    document.getElementById('f-continent').value = '';
    document.getElementById('f-active').checked = true;
    errEl.classList.add('hidden');
    modal.classList.remove('hidden');
}

function openEditModal(c) {
    document.getElementById('modal-title').textContent = 'Edit Country';
    document.getElementById('country-id').value = c.id;
    document.getElementById('f-iso2').value      = c.iso_code_2;
    document.getElementById('f-iso3').value      = c.iso_code_3;
    document.getElementById('f-name-en').value   = c.name_en;
    document.getElementById('f-name-ar').value   = c.name_ar;
    document.getElementById('f-flag').value      = c.flag_emoji ?? '';
    document.getElementById('f-continent').value = c.continent ?? '';
    document.getElementById('f-active').checked  = !!c.is_active;
    errEl.classList.add('hidden');
    modal.classList.remove('hidden');
}

function closeModal() { modal.classList.add('hidden'); }

async function saveCountry() {
    const id  = document.getElementById('country-id').value;
    const url = id
        ? `/admin/travel/countries/${id}`
        : '/admin/travel/countries';
    const method = id ? 'PUT' : 'POST';

    const body = {
        iso_code_2 : document.getElementById('f-iso2').value.toUpperCase(),
        iso_code_3 : document.getElementById('f-iso3').value.toUpperCase(),
        name_en    : document.getElementById('f-name-en').value,
        name_ar    : document.getElementById('f-name-ar').value,
        flag_emoji : document.getElementById('f-flag').value || null,
        continent  : document.getElementById('f-continent').value || null,
        is_active  : document.getElementById('f-active').checked,
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

async function deleteCountry(id, name) {
    if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;

    const res = await fetch(`/admin/travel/countries/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    });
    const json = await res.json();
    if (!res.ok) { alert(json.message); return; }
    document.getElementById('country-row-' + id)?.remove();
}
</script>
@endpush
