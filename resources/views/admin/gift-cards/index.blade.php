@extends('layouts.admin')

@section('title', __('admin.gift_cards_section.title'))

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('content')
    @php
        $columns = [
            [
                'title' => __('admin.gift_cards_section.code'),
                'data' => 'code',
                'name' => 'code',
                'render' => 'function(data){ return "<code class=\"font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded\">" + data + "</code>"; }',
            ],
            [
                'title' => __('admin.gift_cards_section.denomination'),
                'data' => 'denomination_cents',
                'name' => 'denomination_cents',
                'searchable' => false,
                'className' => 'text-end',
                'render' => 'function(data, type, row){ return (data / 100).toFixed(2) + " " + row.currency; }',
            ],
            [
                'title' => __('admin.gift_cards_section.balance'),
                'data' => 'balance_cents',
                'name' => 'balance_cents',
                'searchable' => false,
                'className' => 'text-end',
                'render' => 'function(data, type, row){ return (data / 100).toFixed(2) + " " + row.currency; }',
            ],
            ['title' => __('admin.gift_cards_section.currency'), 'data' => 'currency', 'name' => 'currency', 'searchable' => false],
            [
                'title' => __('admin.gift_cards_section.status'),
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            active:              { label: "' . __('admin.gift_cards_section.active') . '",              color: "success" },
                            redeemed:            { label: "' . __('admin.gift_cards_section.redeemed') . '",            color: "blue"    },
                            expired:             { label: "' . __('admin.gift_cards_section.expired') . '",             color: "gray"    },
                            cancelled:           { label: "' . __('admin.gift_cards_section.cancelled') . '",           color: "red"     },
                            pending_activation:  { label: "' . __('admin.gift_cards_section.pending_activation') . '",  color: "amber"   }
                        })',
            ],
            [
                'title' => __('admin.gift_cards_section.recipient'),
                'data' => 'recipient_name',
                'name' => 'recipient_name',
                'searchable' => false,
                'render' => 'function(data, type, row){ return data || row.recipient_email || "—"; }',
            ],
            [
                'title' => __('admin.gift_cards_section.expiry'),
                'data' => 'expires_at',
                'name' => 'expires_at',
                'searchable' => false,
                'render' => 'Renderers.date',
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-end',
                'render' => 'function(data, type, row) {
                            return Renderers.actions([{ type: "link", label: "' . __('admin.gift_cards_section.view') . '", url: row.show_url }])(data, type, row);
                        }',
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => __('admin.gift_cards_section.code_recipient'), 'placeholder' => __('admin.gift_cards_section.search_placeholder')],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => __('admin.gift_cards_section.status'),
                'options' => [
                    '' => __('admin.gift_cards_section.all'),
                    'active' => __('admin.gift_cards_section.active'),
                    'redeemed' => __('admin.gift_cards_section.redeemed'),
                    'expired' => __('admin.gift_cards_section.expired'),
                    'cancelled' => __('admin.gift_cards_section.cancelled'),
                    'pending_activation' => __('admin.gift_cards_section.pending_activation'),
                ],
            ],
            ['type' => 'text', 'name' => 'currency', 'label' => __('admin.gift_cards_section.currency'), 'placeholder' => 'AED'],
            ['type' => 'date_range', 'name' => 'date', 'label' => __('admin.gift_cards_section.date_range')],
        ];
    @endphp

    <div class="border-b border-gray-200 flex gap-6 mb-4" x-data="{ tab: 'all' }" id="gift-cards-tabs">
        <a href="#" data-status="" class="gc-tab pb-3 border-b-2 border-blue-600 text-blue-600 text-sm font-medium">{{ __('admin.gift_cards_section.all') }}</a>
        <a href="#" data-status="active" class="gc-tab pb-3 border-b-2 border-transparent text-gray-500 text-sm font-medium">{{ __('admin.gift_cards_section.active') }}</a>
        <a href="#" data-status="redeemed" class="gc-tab pb-3 border-b-2 border-transparent text-gray-500 text-sm font-medium">{{ __('admin.gift_cards_section.redeemed') }}</a>
        <a href="#" data-status="expired" class="gc-tab pb-3 border-b-2 border-transparent text-gray-500 text-sm font-medium">{{ __('admin.gift_cards_section.expired') }}</a>
        <a href="#" data-status="cancelled" class="gc-tab pb-3 border-b-2 border-transparent text-gray-500 text-sm font-medium">{{ __('admin.gift_cards_section.cancelled') }}</a>
    </div>

    <x-table.datatable id="gift-cards-table" url="{{ route('admin.gift-cards.datatable') }}" :columns="$columns"
        :filters="$filters"
        :create-action="['url' => route('admin.gift-cards.create'), 'label' => __('admin.gift_cards_section.add_gift_card')]"
        :page-length="25" :order="[[6, 'desc']]" />
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.gc-tab').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.gc-tab').forEach(function (t) {
                    t.classList.remove('border-blue-600', 'text-blue-600');
                    t.classList.add('border-transparent', 'text-gray-500');
                });
                tab.classList.add('border-blue-600', 'text-blue-600');
                tab.classList.remove('border-transparent', 'text-gray-500');

                var status = tab.getAttribute('data-status');
                var statusFilter = document.querySelector('[name="status"]');
                if (statusFilter) {
                    statusFilter.value = status;
                    statusFilter.dispatchEvent(new Event('change'));
                }
                window.reloadDataTable && window.reloadDataTable('gift-cards-table');
            });
        });
    </script>
@endpush
