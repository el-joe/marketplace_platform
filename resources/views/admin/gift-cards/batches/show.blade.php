@extends('layouts.admin')

@section('title', $batch->name)

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $batch->name }}</h1>
            @if($batch->description)
                <p class="text-sm text-gray-500 mt-0.5">{{ $batch->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            <a href="#" class="btn btn-secondary btn-sm">
                {{ __('admin.gift_cards_section.export_csv') }}
            </a>
            @if($batch->inactive_count > 0)
            <button type="button" id="activate-batch-btn" class="btn btn-primary btn-sm">
                {{ __('admin.gift_cards_section.activate_batch') }}
            </button>
            @endif
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 text-sm text-gray-600 bg-white rounded-xl border border-gray-200 p-4 sm:grid-cols-4">
        <div><span class="text-gray-400">{{ __('admin.gift_cards_section.currency_code') }}:</span> {{ $batch->currency_code }}</div>
        <div><span class="text-gray-400">{{ __('admin.gift_cards_section.amount') }}:</span> {{ (int) $batch->amount }} {{ $batch->currency_code }}</div>
        <div><span class="text-gray-400">{{ __('admin.gift_cards_section.expiry') }}:</span> {{ $batch->expires_at?->format('Y-m-d H:i') ?? '—' }}</div>
        <div><span class="text-gray-400">{{ __('admin.gift_cards_section.created_by') }}:</span> {{ $batch->createdByAdmin?->name ?? '—' }} ({{ $batch->created_at->format('Y-m-d H:i') }})</div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        @foreach([
            ['label' => __('admin.gift_cards_section.quantity'), 'value' => $batch->quantity, 'color' => 'gray-700'],
            ['label' => __('admin.gift_cards_section.active_count'), 'value' => $batch->active_count, 'color' => 'emerald-600'],
            ['label' => __('admin.gift_cards_section.redeemed_count'), 'value' => $batch->redeemed_count, 'color' => 'blue-600'],
            ['label' => __('admin.gift_cards_section.expired'), 'value' => $batch->expired_count, 'color' => 'red-600'],
            ['label' => __('admin.gift_cards_section.inactive'), 'value' => $batch->inactive_count, 'color' => 'gray-700'],
        ] as $stat)
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-black text-{{ $stat['color'] }}">
                {{ $stat['value'] }}
            </div>
            <div class="text-xs text-gray-500 mt-1">{{ $stat['label'] }}</div>
        </div>
        @endforeach
    </div>

    @php
        $columns = [
            [
                'title' => __('admin.gift_cards_section.code'),
                'data' => 'code',
                'name' => 'code',
                'render' => 'function(data){ return "<code class=\"font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded\">" + data + "</code>"; }',
            ],
            [
                'title' => __('admin.gift_cards_section.amount'),
                'data' => 'amount',
                'name' => 'amount',
                'searchable' => false,
                'className' => 'text-end',
                'render' => 'function(data, type, row){ return Number(data).toFixed(2) + " " + row.currency_code; }',
            ],
            [
                'title' => __('admin.gift_cards_section.status'),
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            inactive: { label: "' . __('admin.gift_cards_section.inactive') . '", color: "gray"    },
                            active:   { label: "' . __('admin.gift_cards_section.active') . '",   color: "success" },
                            redeemed: { label: "' . __('admin.gift_cards_section.redeemed') . '", color: "blue"    },
                            expired:  { label: "' . __('admin.gift_cards_section.expired') . '",  color: "red"     }
                        })',
            ],
            [
                'title' => __('admin.gift_cards_section.redeemed_by'),
                'data' => 'redeemed_by',
                'name' => 'redeemed_by',
                'searchable' => false,
                'orderable' => false,
                'render' => 'function(data){ return data ? data : "—"; }',
            ],
            ['title' => __('admin.gift_cards_section.redeemed_at'), 'data' => 'redeemed_at', 'name' => 'redeemed_at', 'searchable' => false, 'render' => 'Renderers.date'],
            ['title' => __('admin.gift_cards_section.expiry'), 'data' => 'expires_at', 'name' => 'expires_at', 'searchable' => false, 'render' => 'Renderers.date'],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => __('admin.gift_cards_section.code'), 'placeholder' => __('admin.gift_cards_section.search_placeholder')],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => __('admin.gift_cards_section.status'),
                'options' => [
                    '' => __('admin.gift_cards_section.all'),
                    'inactive' => __('admin.gift_cards_section.inactive'),
                    'active' => __('admin.gift_cards_section.active'),
                    'redeemed' => __('admin.gift_cards_section.redeemed'),
                    'expired' => __('admin.gift_cards_section.expired'),
                ],
            ],
        ];
    @endphp

    <x-table.datatable id="batch-cards-table" url="{{ route('admin.gift-cards.batches.datatable', $batch->id) }}"
        :columns="$columns" :filters="$filters" :page-length="25" :order="[[5, 'desc']]" />
@endsection

@push('scripts')
    <script>
        document.getElementById('activate-batch-btn')?.addEventListener('click', function () {
            if (!confirm(@json(__('admin.gift_cards_section.activate_confirm')))) return;

            fetch(@json(route('admin.gift-cards.batches.activate', $batch->id)), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            })
                .then((res) => res.json())
                .then((data) => {
                    window.Toast
                        ? window.Toast.success(@json(__('admin.gift_cards_section.batch_activated')).replace(':count', data.activated_count))
                        : alert('Activated: ' + data.activated_count);
                    window.location.reload();
                });
        });
    </script>
@endpush
