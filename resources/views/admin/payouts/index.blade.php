@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('title', 'Payouts')

@section('content')
    @php
        $columns = [
            [
                'title' => 'Payout #',
                'data' => 'payout_number',
                'name' => 'payout_number',
                'searchable' => true,
                'render' => 'function(data,t,row){return "<a href=\""+row.show_url+"\" class=\"font-medium text-primary-600 hover:text-primary-800 hover:underline\">"+data+"</a>";}',
            ],
            [
                'title' => 'Vendor',
                'data' => 'vendor_name',
                'name' => 'vendor_name',
                'searchable' => false,
            ],
            [
                'title' => 'Period',
                'data' => 'period',
                'name' => 'period',
                'searchable' => false,
            ],
            [
                'title' => 'Gross Sales',
                'data' => 'gross_formatted',
                'name' => 'gross_formatted',
                'searchable' => false,
                'className' => 'text-right',
            ],
            [
                'title' => 'Net Amount',
                'data' => 'net_formatted',
                'name' => 'net_formatted',
                'searchable' => false,
                'className' => 'text-right font-semibold',
            ],
            [
                'title' => 'Method',
                'data' => 'payout_method',
                'name' => 'payout_method',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            bank_transfer: { label: "Bank Transfer", color: "primary" },
                            wallet:        { label: "Wallet",        color: "primary" },
                            paypal:        { label: "PayPal",        color: "primary" }
                        })',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            pending:    { label: "Pending",    color: "gray"    },
                            approved:   { label: "Approved",   color: "primary" },
                            processing: { label: "Processing", color: "primary" },
                            completed:  { label: "Completed",  color: "success" },
                            failed:     { label: "Failed",     color: "danger"  },
                            on_hold:    { label: "On Hold",    color: "warning" }
                        })',
            ],
            [
                'title' => 'Processed',
                'data' => 'processed_at',
                'name' => 'processed_at',
                'searchable' => false,
                'render' => 'function(data){return data ? Renderers.dateAgo(data) : "<span class=\"text-gray-300\">—</span>";}',
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
            ['type' => 'text', 'name' => 'search', 'label' => 'Payout #', 'placeholder' => 'Search payout #…'],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => [
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'processing' => 'Processing',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'on_hold' => 'On Hold',
                ],
            ],
            [
                'type' => 'select',
                'name' => 'currency',
                'label' => 'Currency',
                'options' => ['USD' => 'USD', 'SAR' => 'SAR', 'AED' => 'AED', 'KWD' => 'KWD'],
            ],
            ['type' => 'date_range', 'name' => 'date', 'label' => 'Period'],
            ['type' => 'text', 'name' => 'min_amount', 'label' => 'Min Net', 'placeholder' => 'e.g. 100'],
        ];
    @endphp

    <x-table.datatable id="payouts-table" url="{{ route('admin.payouts.datatable') }}" :columns="$columns"
        :filters="$filters" :page-length="25" :order="[[7, 'desc']]" />
@endsection