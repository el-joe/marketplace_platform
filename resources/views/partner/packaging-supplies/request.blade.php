@extends('layouts.partner')

@section('title', __('partner.packaging_supplies.request_supplies'))

@section('content')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('partner.packaging-supplies.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('partner.packaging_supplies.back_to_catalog') }}</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">{{ __('partner.packaging_supplies.request_supplies') }}</h1>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('partner.packaging-supplies.submit') }}" id="requestForm">
        @csrf

        <div class="grid grid-cols-3 gap-6">

            {{-- ─── Supply selection ───────────────────────────────────────────── --}}
            <div class="col-span-2 space-y-4">

                <div class="card overflow-hidden">
                    <div class="px-5 py-3 border-b bg-gray-50 font-medium text-sm text-gray-700">{{ __('partner.packaging_supplies.select_items') }}</div>
                    <div class="divide-y divide-gray-100">
                        @foreach($supplies as $supply)
                            <div class="px-5 py-4 flex items-center gap-4" x-data="{ qty: 0 }">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 text-sm">{{ $supply->name_en }}</span>
                                        <span class="badge {{ $supply->typeBadgeClass() }}">{{ ucfirst($supply->type->value) }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-0.5">
                                        {{ $supply->name_ar }}
                                        @if($supply->size) · {{ $supply->size }} @endif
                                    </div>
                                </div>
                                <div class="text-sm font-semibold {{ $supply->isFree() ? 'text-green-600' : 'text-gray-700' }} w-20 text-right">
                                    {{ $supply->unit_cost_formatted }}
                                </div>
                                <div class="flex items-center gap-2 w-36">
                                    <button type="button" onclick="adjustQty('qty_{{ $supply->id }}', -1)"
                                            class="w-7 h-7 rounded-full border border-gray-300 text-gray-500 hover:bg-gray-100 flex items-center justify-center text-lg leading-none">−</button>
                                    <input type="number" id="qty_{{ $supply->id }}"
                                           name="items[{{ $loop->index }}][quantity]"
                                           value="{{ old('items.'.$loop->index.'.quantity', 0) }}"
                                           min="0" max="1000"
                                           data-name="{{ $supply->name_en }}"
                                           data-unit-cost-cents="{{ $supply->unit_cost_cents }}"
                                           class="w-14 text-center input text-sm py-1"
                                           onchange="syncItem(this, '{{ $supply->id }}')">
                                    <button type="button" onclick="adjustQty('qty_{{ $supply->id }}', 1)"
                                            class="w-7 h-7 rounded-full border border-gray-300 text-gray-500 hover:bg-gray-100 flex items-center justify-center text-lg leading-none">+</button>
                                    <input type="hidden" name="items[{{ $loop->index }}][supply_id]"
                                           value="{{ $supply->id }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- ─── Sidebar ─────────────────────────────────────────────────── --}}
            <div class="space-y-4">
                <div class="card p-5 space-y-4">

                    @if($warehouses->isNotEmpty())
                        <div>
                            <label class="label">{{ __('partner.packaging_supplies.delivery_warehouse') }} <span class="text-gray-400">{{ __('partner.packaging_supplies.optional') }}</span></label>
                            <select name="warehouse_id" class="input text-sm">
                                <option value="">{{ __('partner.packaging_supplies.ship_to_my_address') }}</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}" @selected(old('warehouse_id') === $wh->id)>{{ $wh->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">{{ __('partner.packaging_supplies.fbn_warehouse_hint') }}</p>
                        </div>
                    @endif

                    <div>
                        <label class="label">{{ __('partner.packaging_supplies.notes') }} <span class="text-gray-400">{{ __('partner.packaging_supplies.optional') }}</span></label>
                        <textarea name="notes" rows="3" class="input text-sm resize-none"
                                  placeholder="{{ __('partner.packaging_supplies.notes_placeholder') }}">{{ old('notes') }}</textarea>
                    </div>

                    <button type="button" class="btn btn-primary w-full" onclick="openConfirmModal()">{{ __('partner.packaging_supplies.submit_request_btn') }}</button>

                    <a href="{{ route('partner.packaging-supplies.index') }}"
                       class="btn btn-secondary w-full text-center block">{{ __('common.cancel') }}</a>
                </div>
            </div>

        </div>
    </form>

    {{-- ─── Confirmation Modal ─────────────────────────────────────────────── --}}
    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
            <div class="px-5 py-3 border-b font-medium text-gray-900">{{ __('partner.packaging_supplies.confirm_order') }}</div>
            <div class="p-5 space-y-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-gray-500 text-xs">
                            <th class="text-start font-normal pb-2">{{ __('partner.packaging_supplies.item') }}</th>
                            <th class="text-center font-normal pb-2">{{ __('partner.packaging_supplies.qty') }}</th>
                            <th class="text-end font-normal pb-2">{{ __('partner.packaging_supplies.unit_price') }}</th>
                            <th class="text-end font-normal pb-2">{{ __('partner.packaging_supplies.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody id="confirmItemsBody"></tbody>
                </table>
                <div class="border-t pt-3 space-y-1 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>{{ __('partner.packaging_supplies.items_subtotal') }}</span>
                        <span id="confirmSubtotal">0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>{{ __('partner.packaging_supplies.delivery_fee') }}</span>
                        <span id="confirmDeliveryFee">…</span>
                    </div>
                    <div class="flex justify-between font-bold text-gray-900 text-base pt-1">
                        <span>{{ __('partner.packaging_supplies.grand_total') }}</span>
                        <span id="confirmGrandTotal">0.00</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400">{{ __('partner.packaging_supplies.delivery_fee_payout_note') }}</p>
            </div>
            <div class="px-5 py-3 border-t flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">{{ __('common.cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('requestForm').submit()">{{ __('partner.packaging_supplies.confirm_and_submit') }}</button>
            </div>
        </div>
    </div>

    <script>
        function adjustQty(id, delta) {
            const input = document.getElementById(id);
            const newVal = Math.max(0, Math.min(1000, (parseInt(input.value) || 0) + delta));
            input.value = newVal;
            input.dispatchEvent(new Event('change'));
        }

        function selectedItems() {
            return Array.from(document.querySelectorAll('input[type="number"][id^="qty_"]'))
                .filter(q => parseInt(q.value) > 0)
                .map(q => ({
                    name: q.dataset.name,
                    qty: parseInt(q.value),
                    unitCostCents: parseInt(q.dataset.unitCostCents),
                }));
        }

        function money(cents) {
            return (cents / 100).toFixed(2);
        }

        function openConfirmModal() {
            const items = selectedItems();
            if (items.length === 0) {
                alert(@json(__('partner.packaging_supplies.select_item_alert')));
                return;
            }

            const tbody = document.getElementById('confirmItemsBody');
            tbody.innerHTML = '';
            let subtotalCents = 0;
            items.forEach(item => {
                const lineCents = item.unitCostCents * item.qty;
                subtotalCents += lineCents;
                const row = document.createElement('tr');
                row.innerHTML = `<td class="py-1">${item.name}</td>
                    <td class="text-center py-1">${item.qty}</td>
                    <td class="text-end py-1">${money(item.unitCostCents)}</td>
                    <td class="text-end py-1">${money(lineCents)}</td>`;
                tbody.appendChild(row);
            });

            document.getElementById('confirmSubtotal').textContent = money(subtotalCents);
            document.getElementById('confirmDeliveryFee').textContent = '…';
            document.getElementById('confirmGrandTotal').textContent = money(subtotalCents);
            document.getElementById('confirmModal').classList.remove('hidden');
            document.getElementById('confirmModal').classList.add('flex');

            fetch(@json(route('partner.packaging-supplies.delivery-fee')))
                .then(res => res.json())
                .then(data => {
                    document.getElementById('confirmDeliveryFee').textContent = money(data.fee_cents);
                    document.getElementById('confirmGrandTotal').textContent = money(subtotalCents + data.fee_cents);
                })
                .catch(() => {
                    document.getElementById('confirmDeliveryFee').textContent = money(0);
                });
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            document.getElementById('confirmModal').classList.remove('flex');
        }
    </script>

@endsection
