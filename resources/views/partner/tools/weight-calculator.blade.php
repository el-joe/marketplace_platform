@extends('layouts.partner')

@section('title', 'Weight Calculator')

@section('content')
<div class="p-6 space-y-6" x-data="weightCalculator()">

    <div>
        <h1 class="text-xl font-bold text-gray-900">Vendor Weight Calculator</h1>
        <p class="text-sm text-gray-500 mt-0.5">Estimate the volumetric weight and extra shipping fee for a package before you ship it.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- LEFT COLUMN — Calculator form --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700">Package Details</h2>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Length (cm)</label>
                    <input type="number" step="0.1" min="0.1" x-model.number="form.length_cm"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Width (cm)</label>
                    <input type="number" step="0.1" min="0.1" x-model.number="form.width_cm"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Height (cm)</label>
                    <input type="number" step="0.1" min="0.1" x-model.number="form.height_cm"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Actual Weight (kg)</label>
                <input type="number" step="0.01" min="0.01" x-model.number="form.actual_weight_kg"
                    class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Shipping Method</label>
                <select x-model="form.shipping_method_id"
                    class="w-full max-w-xs rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option value="">Select a method</option>
                    @foreach($methods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Live formula preview --}}
            <div class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
                Volumetric = L &times; W &times; H &divide; 5000
                <span class="font-mono">
                    (<span x-text="form.length_cm || 0"></span> &times; <span x-text="form.width_cm || 0"></span> &times; <span x-text="form.height_cm || 0"></span>) &divide; 5000
                    = <span class="font-semibold text-gray-700" x-text="volumetricPreview + ' kg'"></span>
                </span>
            </div>

            <button type="button" @click="calculate()" :disabled="loading || !canCalculate"
                class="bg-blue-600 text-white rounded-xl px-5 py-2 text-sm font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Calculate</span>
                <span x-show="loading">Calculating…</span>
            </button>

            <p x-show="error" x-text="error" class="text-sm text-red-600"></p>
        </div>

        {{-- RIGHT COLUMN — Result --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4" x-show="result" x-cloak>
            <h2 class="text-sm font-semibold text-gray-700">Result</h2>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Volumetric Weight</dt>
                    <dd class="font-medium text-gray-900" x-text="result?.volumetric_weight_kg + ' kg'"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Actual Weight</dt>
                    <dd class="font-medium text-gray-900" x-text="result?.actual_weight_kg + ' kg'"></dd>
                </div>
                <div class="flex justify-between items-center border-t pt-2">
                    <dt class="text-gray-700 font-semibold">Effective Weight</dt>
                    <dd>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800"
                            x-text="'✅ ' + result?.effective_weight_kg + ' kg'"></span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Heavier Basis</dt>
                    <dd class="font-medium text-gray-900" x-text="result?.is_volumetric_heavier ? 'Volumetric weight' : 'Actual weight'"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Slab</dt>
                    <dd class="font-medium text-gray-900" x-text="result?.slab_label ? ('Falls in range: ' + result.slab_label) : '—'"></dd>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <dt class="text-gray-700 font-semibold">Extra Fee for this Weight</dt>
                    <dd class="font-bold text-gray-900"
                        x-text="result?.slab_extra_fee_cents > 0 ? (result.currency + ' ' + formatCents(result.slab_extra_fee_cents)) : 'Included in base rate'"></dd>
                </div>
            </dl>

            <div class="text-xs text-blue-800 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                Any extra weight fee will be deducted from your earnings per order in your financial statements.
            </div>
        </div>
    </div>

    {{-- Weight Slabs Reference Table --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <h2 class="text-sm font-semibold text-gray-700">Weight Slabs for Your Country</h2>

        @forelse($slabs as $methodSlabs)
            <div class="space-y-2">
                <h3 class="text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ optional($methodSlabs->first()->shippingMethod)->name ?? '—' }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 border-b">
                                <th class="py-2 pr-4">Weight Range</th>
                                <th class="py-2 pr-4">Extra Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($methodSlabs as $slab)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 pr-4 text-gray-700">
                                        {{ rtrim(rtrim(number_format($slab->min_weight_grams / 1000, 2), '0'), '.') }} kg
                                        &ndash;
                                        {{ $slab->max_weight_grams !== null ? rtrim(rtrim(number_format($slab->max_weight_grams / 1000, 2), '0'), '.') . ' kg' : '+' }}
                                    </td>
                                    <td class="py-2 pr-4 text-gray-700">{{ $currency }} {{ number_format($slab->extra_fee_cents / 100, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-400">No weight slabs configured for your country yet.</p>
        @endforelse
    </div>

</div>

@push('scripts')
<script>
function weightCalculator() {
    return {
        form: {
            length_cm: '',
            width_cm: '',
            height_cm: '',
            actual_weight_kg: '',
            shipping_method_id: '',
        },
        result: null,
        loading: false,
        error: null,

        get volumetricPreview() {
            const { length_cm, width_cm, height_cm } = this.form;
            if (!length_cm || !width_cm || !height_cm) return '0';
            return ((length_cm * width_cm * height_cm) / 5000).toFixed(2);
        },

        get canCalculate() {
            const f = this.form;
            return f.length_cm > 0 && f.width_cm > 0 && f.height_cm > 0
                && f.actual_weight_kg > 0 && f.shipping_method_id;
        },

        formatCents(cents) {
            if (cents === undefined || cents === null) return '0.00';
            return (cents / 100).toFixed(2);
        },

        calculate() {
            this.loading = true;
            this.error = null;
            this.result = null;

            fetch('{{ route("partner.tools.weight-calculator.calculate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(this.form),
            })
            .then(async (r) => {
                const data = await r.json();
                if (!r.ok) throw new Error(data.error || data.message || 'Calculation failed.');
                return data;
            })
            .then((data) => {
                this.result = data;
            })
            .catch((e) => {
                this.error = e.message;
            })
            .finally(() => {
                this.loading = false;
            });
        },
    };
}
</script>
@endpush
@endsection
