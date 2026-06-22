@extends('layouts.delivery')

@section('title', 'Order #' . ($assignment->subOrder?->sub_order_number ?? substr($assignment->id, 0, 8)))

@section('header-left')
    <a href="{{ route('delivery.assignments.index') }}" class="flex items-center gap-1 text-slate-300 text-sm -ml-1 p-1">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        Back
    </a>
@endsection

@section('content')

    @php
        /** @var \App\Models\DeliveryAssignment $assignment */
        $isActive = in_array($assignment->status, ['assigned', 'accepted', 'picked_up']);
        $chipClass = 'chip-' . $assignment->status;
        $order = $assignment->subOrder?->order;
        $items = $assignment->subOrder?->items ?? collect();
        $customer = $order?->customer;
    @endphp

    {{-- ── Status + Order Number ───────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">#{{ $assignment->subOrder?->sub_order_number ?? substr($assignment->id, 0, 8) }}
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">Assigned {{ $assignment->assigned_at?->diffForHumans() }}</p>
        </div>
        <span
            class="chip {{ $chipClass }} text-sm px-3 py-1">{{ ucfirst(str_replace('_', ' ', $assignment->status)) }}</span>
    </div>

    {{-- ── Map: pickup → delivery ──────────────────────────────────────────────── --}}
    @if($assignment->subOrder?->order)
        <div class="d-card mb-4 p-0 overflow-hidden rounded-2xl" style="height: 180px;" id="route-map">
            <div class="flex items-center justify-center h-full text-slate-500 text-sm" id="map-placeholder">
                📍 Loading map…
            </div>
        </div>
    @endif

    {{-- ── Addresses ────────────────────────────────────────────────────────────── --}}
    <div class="d-card mb-3">
        <div class="space-y-4">
            {{-- Pickup --}}
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span class="text-yellow-400 text-xs font-bold">P</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Pickup</p>
                    <p class="text-sm text-slate-200 mt-0.5">
                        {{ $assignment->shipment?->subOrder?->vendor?->store_name ?? 'Vendor Warehouse' }}
                    </p>
                </div>
            </div>

            <div class="ml-4 border-l border-dashed border-slate-600 pl-7 -mt-1 -mb-1 h-5"></div>

            {{-- Delivery --}}
            <div class="flex gap-3">
                <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span class="text-green-400 text-xs font-bold">D</span>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Deliver To</p>
                    @if($order?->shipping_address_snapshot)
                                <p class="text-sm text-slate-200 mt-0.5">
                                    {{ is_array($order->shipping_address_snapshot)
                        ? implode(', ', array_filter([
                            $order->shipping_address_snapshot['street'] ?? null,
                            $order->shipping_address_snapshot['city'] ?? null,
                            $order->shipping_address_snapshot['country'] ?? null,
                        ]))
                        : $order->shipping_address_snapshot }}
                                </p>
                    @else
                        <p class="text-sm text-slate-500 mt-0.5">Address not available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Customer Contact ─────────────────────────────────────────────────────── --}}
    @if($customer?->phone && $isActive)
        <div class="d-card mb-3 flex items-center justify-between">
            <div>
                <p class="text-xs text-slate-400">Customer</p>
                <p class="text-sm font-semibold">{{ $customer->name }}</p>
            </div>
            <a href="tel:{{ $customer->phone }}"
                class="flex items-center gap-2 bg-green-600 text-white rounded-xl px-4 py-2.5 text-sm font-semibold">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                Call
            </a>
        </div>
    @endif

    {{-- ── Items ────────────────────────────────────────────────────────────────── --}}
    <div class="d-card mb-4">
        <details>
            <summary class="flex items-center justify-between cursor-pointer list-none">
                <span class="text-sm font-semibold">
                    Items
                    <span class="text-slate-400 font-normal">({{ $items->count() }})</span>
                </span>
                <svg class="w-4 h-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </summary>
            <div class="mt-3 space-y-2 border-t border-slate-700 pt-3">
                @forelse($items as $item)
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-300">{{ $item->productVariant?->product?->name_en ?? 'Product' }}</span>
                        <span class="text-slate-400">× {{ $item->quantity }}</span>
                    </div>
                @empty
                    <p class="text-slate-500 text-sm">No items found.</p>
                @endforelse
            </div>
        </details>
    </div>

    {{-- ── Action Buttons ───────────────────────────────────────────────────────── --}}
    @if($isActive)
        <div x-data="assignmentActions()" class="space-y-3 pb-6">

            {{-- ASSIGNED: Accept button --}}
            @if($assignment->status === 'assigned')
                <button type="button" @click="accept()" :disabled="loading" class="btn-action btn-yellow">
                    <span x-text="loading ? 'Processing…' : '✓ Accept Assignment'"></span>
                </button>
            @endif

            {{-- ACCEPTED: Mark Picked Up --}}
            @if($assignment->status === 'accepted')
                <button type="button" @click="pickUp()" :disabled="loading" class="btn-action btn-blue">
                    <span x-text="loading ? 'Getting location…' : '📦 Mark as Picked Up'"></span>
                </button>
            @endif

            {{-- PICKED_UP: OTP + Deliver --}}
            @if($assignment->status === 'picked_up')
                <div class="d-card mb-3">
                    <p class="text-sm font-semibold mb-4 text-center">Enter Delivery OTP</p>
                    <div class="flex justify-center gap-2 mb-4" id="otp-inputs">
                        @for($i = 0; $i < 6; $i++)
                            <input type="tel" inputmode="numeric" pattern="[0-9]" maxlength="1"
                                class="otp-digit w-11 h-14 rounded-xl bg-slate-700 text-center text-2xl font-bold text-white border-2 border-slate-600 focus:border-yellow-400 focus:outline-none transition-colors"
                                autocomplete="off">
                        @endfor
                        <input type="hidden" id="otp-combined" x-model="otp">
                    </div>

                    {{-- Proof photo --}}
                    <div class="mt-4 border-t border-slate-700 pt-4">
                        <label class="text-xs font-semibold text-slate-400 uppercase tracking-wide block mb-2">Proof Photo
                            (optional)</label>
                        <label
                            class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-slate-600 rounded-xl cursor-pointer hover:border-yellow-400 transition-colors"
                            x-bind:class="proofPreview ? 'border-green-500' : ''">
                            <template x-if="!proofPreview">
                                <div class="text-center">
                                    <svg class="w-7 h-7 text-slate-500 mx-auto" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2" />
                                        <circle cx="8.5" cy="8.5" r="1.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 15l-5-5L5 21" />
                                    </svg>
                                    <p class="text-xs text-slate-500 mt-1">Tap to take / upload photo</p>
                                </div>
                            </template>
                            <template x-if="proofPreview">
                                <img :src="proofPreview" class="h-full w-full object-cover rounded-xl">
                            </template>
                            <input type="file" accept="image/*" capture="environment" class="hidden"
                                @change="handleProofPhoto($event)">
                        </label>
                    </div>
                </div>

                <button type="button" id="deliver-form" @click="deliver()" :disabled="loading || !otp || otp.length < 6"
                    class="btn-action btn-yellow" :class="{ 'opacity-50': !otp || otp.length < 6 }">
                    <span x-text="loading ? 'Confirming…' : '✓ Confirm Delivery'"></span>
                </button>
            @endif

            {{-- Fail button (available for accepted and picked_up) --}}
            @if(in_array($assignment->status, ['accepted', 'picked_up']))
                <button type="button" @click="showFail = true" class="btn-action btn-outline text-sm" style="min-height: 46px;">
                    Mark as Failed
                </button>
            @endif

            {{-- Fail reason modal --}}
            <div x-show="showFail" x-cloak class="fixed inset-0 bg-black/70 z-50 flex items-end justify-center"
                style="max-width: 480px; left: 50%; transform: translateX(-50%);">
                <div class="bg-slate-800 w-full rounded-t-3xl p-6 pb-safe" @click.stop>
                    <h3 class="text-base font-bold mb-4">Why did the delivery fail?</h3>
                    <select x-model="failReason"
                        class="w-full bg-slate-700 text-slate-200 rounded-xl p-3 mb-3 text-sm border border-slate-600 focus:outline-none focus:border-yellow-400">
                        <option value="">Select a reason…</option>
                        <option value="customer_not_home">Customer not home</option>
                        <option value="wrong_address">Wrong address</option>
                        <option value="customer_refused">Customer refused delivery</option>
                        <option value="damaged_package">Package damaged</option>
                        <option value="other">Other</option>
                    </select>
                    <textarea x-model="failNotes" rows="2"
                        class="w-full bg-slate-700 text-slate-200 rounded-xl p-3 mb-4 text-sm border border-slate-600 focus:outline-none focus:border-yellow-400 resize-none"
                        placeholder="Additional notes (optional)"></textarea>
                    <div class="flex gap-3">
                        <button type="button" @click="showFail = false" class="btn-action btn-outline flex-1"
                            style="min-height:46px; font-size:14px;">Cancel</button>
                        <button type="button" @click="fail()" :disabled="!failReason || loading"
                            class="btn-action btn-red flex-1" style="min-height:46px; font-size:14px;"
                            :class="{ 'opacity-50': !failReason }">
                            <span x-text="loading ? 'Saving…' : 'Confirm'"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Success/Error toast --}}
            <div x-show="toastMsg" x-cloak x-transition
                class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-5 py-3 rounded-2xl text-sm font-semibold shadow-xl"
                :class="toastError ? 'bg-red-600 text-white' : 'bg-green-600 text-white'" style="max-width: 340px;">
                <span x-text="toastMsg"></span>
            </div>

        </div>
    @else
        {{-- Completed / Read-only state --}}
        <div class="d-card text-center py-8 mb-6">
            @if($assignment->status === 'delivered')
                <div class="text-4xl mb-2">✅</div>
                <p class="font-semibold text-green-400">Delivered</p>
                @if($assignment->delivered_at)
                    <p class="text-xs text-slate-400 mt-1">{{ $assignment->delivered_at->format('d M Y H:i') }}</p>
                @endif
            @elseif($assignment->status === 'failed')
                <div class="text-4xl mb-2">❌</div>
                <p class="font-semibold text-red-400">Failed</p>
                @if($assignment->failure_reason)
                    <p class="text-xs text-slate-400 mt-1">{{ ucfirst(str_replace('_', ' ', $assignment->failure_reason)) }}</p>
                @endif
            @endif
        </div>
    @endif

@endsection

@push('scripts')
    <script>
        function assignmentActions() {
            return {
                loading: false,
                otp: '',
                proofFile: null,
                proofPreview: null,
                showFail: false,
                failReason: '',
                failNotes: '',
                toastMsg: '',
                toastError: false,
                _toastTimer: null,

                toast(msg, error = false) {
                    this.toastMsg = msg;
                    this.toastError = error;
                    clearTimeout(this._toastTimer);
                    this._toastTimer = setTimeout(() => { this.toastMsg = ''; }, 3000);
                },

                getLocation() {
                    return new Promise((resolve) => {
                        if (!navigator.geolocation) return resolve({});
                        navigator.geolocation.getCurrentPosition(
                            p => resolve({ latitude: p.coords.latitude, longitude: p.coords.longitude }),
                            () => resolve({})
                        );
                    });
                },

                async accept() {
                    this.loading = true;
                    try {
                        const res = await fetch(@json(route('delivery.assignments.accept', $assignment->id)), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.toast('Assignment accepted!');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            this.toast(data.message || 'Failed.', true);
                        }
                    } catch (e) { this.toast('Network error.', true); }
                    finally { this.loading = false; }
                },

                async pickUp() {
                    this.loading = true;
                    const loc = await this.getLocation();
                    try {
                        const res = await fetch(@json(route('delivery.assignments.picked-up', $assignment->id)), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify(loc),
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.toast('Marked as picked up!');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            this.toast(data.message || 'Failed.', true);
                        }
                    } catch (e) { this.toast('Network error.', true); }
                    finally { this.loading = false; }
                },

                handleProofPhoto(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.proofFile = file;
                    this.proofPreview = URL.createObjectURL(file);
                },

                async deliver() {
                    if (!this.otp || this.otp.length < 6) return;
                    this.loading = true;
                    const loc = await this.getLocation();
                    const form = new FormData();
                    form.append('otp_code', this.otp);
                    form.append('_token', @json(csrf_token()));
                    if (loc.latitude) form.append('latitude', loc.latitude);
                    if (loc.longitude) form.append('longitude', loc.longitude);
                    if (this.proofFile) form.append('proof_image', this.proofFile);

                    try {
                        const res = await fetch(@json(route('delivery.assignments.deliver', $assignment->id)), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: form,
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.toast('Delivery confirmed! 🎉');
                            setTimeout(() => window.location.href = @json(route('delivery.assignments.index')), 1200);
                        } else {
                            this.toast(data.message || 'Invalid OTP.', true);
                            if (data.remaining === 0) {
                                setTimeout(() => location.reload(), 1500);
                            }
                        }
                    } catch (e) { this.toast('Network error.', true); }
                    finally { this.loading = false; }
                },

                async fail() {
                    if (!this.failReason) return;
                    this.loading = true;
                    const loc = await this.getLocation();
                    try {
                        const res = await fetch(@json(route('delivery.assignments.fail', $assignment->id)), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                failure_reason: this.failReason,
                                failure_notes: this.failNotes,
                                ...loc
                            }),
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.showFail = false;
                            this.toast('Marked as failed.');
                            setTimeout(() => window.location.href = @json(route('delivery.assignments.index')), 1200);
                        } else {
                            this.toast(data.message || 'Failed.', true);
                        }
                    } catch (e) { this.toast('Network error.', true); }
                    finally { this.loading = false; }
                },
            };
        }

        // Wire OTP inputs (from delivery/app.js)
        document.addEventListener('DOMContentLoaded', () => {
            const inputs = document.querySelectorAll('.otp-digit');
            if (!inputs.length) return;
            inputs.forEach((input, i) => {
                input.addEventListener('input', e => {
                    const v = e.target.value.replace(/\D/g, '');
                    e.target.value = v.slice(0, 1);
                    if (v && i < inputs.length - 1) inputs[i + 1].focus();
                    const combined = [...inputs].map(el => el.value).join('');
                    document.getElementById('otp-combined').value = combined;
                    // Tell Alpine
                    document.getElementById('otp-combined').dispatchEvent(new Event('input'));
                });
                input.addEventListener('keydown', e => {
                    if (e.key === 'Backspace' && !input.value && i > 0) inputs[i - 1].focus();
                });
                input.addEventListener('paste', e => {
                    e.preventDefault();
                    const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                    inputs.forEach((el, idx) => { el.value = pasted[idx] || ''; });
                    if (pasted.length >= inputs.length) inputs[inputs.length - 1].focus();
                    const combined = pasted.slice(0, inputs.length);
                    document.getElementById('otp-combined').value = combined;
                    document.getElementById('otp-combined').dispatchEvent(new Event('input'));
                });
            });
        });
    </script>
@endpush
