@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Orders')

@section('content')
    @php
        $columns = [
            [
                'title' => 'Order #',
                'data' => 'order_number',
                'name' => 'order_number',
                'searchable' => true,
                'render' => 'function(data,t,row){return "<a href=\""+row.show_url+"\" class=\"font-medium text-primary-600 hover:text-primary-800 hover:underline\">"+data+"</a>";}',
            ],
            [
                'title' => 'Customer',
                'data' => 'customer',
                'name' => 'customer',
                'orderable' => false,
                'searchable' => false,
            ],
            [
                'title' => 'Items',
                'data' => 'items_count',
                'name' => 'items_count',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
            ],
            [
                'title' => 'Total',
                'data' => 'total_formatted',
                'name' => 'total_formatted',
                'searchable' => false,
                'className' => 'text-right font-medium',
            ],
            [
                'title' => 'Payment',
                'data' => 'payment_method',
                'name' => 'payment_method',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            card:          { label: "Card",          color: "primary" },
                            wallet:        { label: "Wallet",        color: "primary" },
                            cod:           { label: "Cash on Del.",  color: "gray"    },
                            bnpl:          { label: "BNPL",          color: "warning" },
                            bank_transfer: { label: "Bank Transfer", color: "gray"    }
                        })',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            placed:              { label: "Placed",          color: "gray"    },
                            confirmed:           { label: "Confirmed",       color: "primary" },
                            partially_shipped:   { label: "Part. Shipped",   color: "primary" },
                            shipped:             { label: "Shipped",         color: "primary" },
                            partially_delivered: { label: "Part. Delivered", color: "primary" },
                            delivered:           { label: "Delivered",       color: "success" },
                            completed:           { label: "Completed",       color: "success" },
                            cancelled:           { label: "Cancelled",       color: "danger"  },
                            refunded:            { label: "Refunded",        color: "warning" },
                            disputed:            { label: "Disputed",        color: "danger"  }
                        })',
            ],
            [
                'title' => 'Risk',
                'data' => 'risk_score',
                'name' => 'risk_score',
                'searchable' => false,
                'className' => 'text-center',
                'render' => 'function(data){
                            if(data===null||data===undefined){return "<span class=\"text-gray-300\">—</span>";}
                            var n=Math.round(data);
                            var cls=n>=70?"bg-red-100 text-red-700 ring-red-200":n>=40?"bg-amber-100 text-amber-800 ring-amber-200":"bg-green-100 text-green-700 ring-green-200";
                            return "<span class=\"inline-flex items-center justify-center w-9 h-9 rounded-full text-xs font-bold ring-1 "+cls+"\">"+n+"</span>";
                        }',
            ],
            [
                'title' => 'Placed',
                'data' => 'placed_at',
                'name' => 'placed_at',
                'searchable' => false,
                'render' => 'Renderers.dateAgo',
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'Renderers.actions([
                            { type: "link", label: "View", url: ":show_url", class: "btn-primary btn-sm" }
                        ])',
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Order #', 'placeholder' => 'Search order #…'],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => [
                    'placed' => 'Placed',
                    'confirmed' => 'Confirmed',
                    'partially_shipped' => 'Partially Shipped',
                    'shipped' => 'Shipped',
                    'partially_delivered' => 'Partially Delivered',
                    'delivered' => 'Delivered',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    'refunded' => 'Refunded',
                    'disputed' => 'Disputed',
                ],
            ],
            [
                'type' => 'select',
                'name' => 'payment_status',
                'label' => 'Payment',
                'options' => [
                    'pending' => 'Pending',
                    'authorized' => 'Authorized',
                    'captured' => 'Captured',
                    'failed' => 'Failed',
                    'refunded' => 'Refunded',
                    'partially_refunded' => 'Partially Refunded',
                ],
            ],
            [
                'type' => 'select',
                'name' => 'country_id',
                'label' => 'Country',
                'options' => $countries->toArray(),
            ],
            ['type' => 'date_range', 'name' => 'date', 'label' => 'Placed date'],
            ['type' => 'text', 'name' => 'min_total', 'label' => 'Min Total', 'placeholder' => 'e.g. 50'],
            ['type' => 'text', 'name' => 'max_total', 'label' => 'Max Total', 'placeholder' => 'e.g. 500'],
            [
                'type' => 'select',
                'name' => 'risk_score_min',
                'label' => 'Risk',
                'options' => ['70' => 'High risk only (≥ 70)', '40' => 'Medium+ risk (≥ 40)'],
            ],
        ];
    @endphp

    <x-table.datatable id="orders-table" url="{{ route('admin.orders.datatable') }}" :columns="$columns" :filters="$filters"
        :page-length="25" :order="[[7, 'desc']]" />
@endsection

@push('scripts')
@endpush