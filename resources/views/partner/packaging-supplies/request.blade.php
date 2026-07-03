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
                                        <span class="badge {{ $supply->typeBadgeClass() }}">{{ ucfirst($supply->type) }}</span>
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

                    <button type="submit" class="btn btn-primary w-full"
                            onclick="return validateForm()">{{ __('partner.packaging_supplies.submit_request_btn') }}</button>

                    <a href="{{ route('partner.packaging-supplies.index') }}"
                       class="btn btn-secondary w-full text-center block">{{ __('common.cancel') }}</a>
                </div>
            </div>

        </div>
    </form>

    <script>
        function adjustQty(id, delta) {
            const input = document.getElementById(id);
            const newVal = Math.max(0, Math.min(1000, (parseInt(input.value) || 0) + delta));
            input.value = newVal;
        }

        function validateForm() {
            const quantities = document.querySelectorAll('input[type="number"][id^="qty_"]');
            const hasAny = Array.from(quantities).some(q => parseInt(q.value) > 0);
            if (!hasAny) {
                alert(@json(__('partner.packaging_supplies.select_item_alert')));
                return false;
            }
            return true;
        }
    </script>

@endsection
