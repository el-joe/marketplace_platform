import $ from 'jquery';

const activeBadge = {
    true: '<span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700">Active</span>',
    false: '<span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Inactive</span>',
};

function initZonesTable() {
    window.initDataTable('exceptional-zones-table', {
        url: window.VENDOR_EXCEPTIONAL_ZONES_ROUTES.index,
        ajaxMethod: 'GET',
        order: [[3, 'desc']],
        columns: [
            { data: 'zone_name' },
            { data: 'country' },
            {
                data: 'is_active',
                className: 'text-center',
                orderable: false,
                render: (value) => activeBadge[value] ?? activeBadge.false,
            },
            { data: 'created_at' },
        ],
        pageLength: 25,
    });
}

$(function () {
    if (!document.getElementById('exceptional-zones-table')) return;
    initZonesTable();
});
