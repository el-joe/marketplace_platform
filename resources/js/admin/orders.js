import $ from 'jquery';

$(function () {

    // ── Sub-order accordion ──────────────────────────────────────────────────

    $(document).on('click', '.sub-order-toggle, .sub-order-header', function (e) {
        if ($(e.target).closest('button').length && !$(e.target).closest('.sub-order-toggle').length) {
            return;
        }
        const $header = $(this).closest('.sub-order-header');
        const $body = $header.next('.sub-order-body');
        const $icon = $header.find('.toggle-icon');
        $body.slideToggle(200);
        $icon.toggleClass('rotate-180');
    });

    // Open first sub-order by default
    $('.sub-order-header').first().next('.sub-order-body').show();
    $('.sub-order-header').first().find('.toggle-icon').addClass('rotate-180');

    // ── Partial amount toggle ────────────────────────────────────────────────

    $('input[name="refund_type"]').on('change', function () {
        $('#partial-amount-field').toggleClass('hidden', $(this).val() !== 'partial');
    });

    // ── Generic AJAX form submitter ──────────────────────────────────────────

    function clearFormErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-error]').text('').addClass('hidden');
    }

    function submitOrderAction(formId, url, onSuccess) {
        const $form = $('#' + formId);
        const $btn = $form.find('[type="submit"]');

        clearFormErrors($form);
        $btn.prop('disabled', true);

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $form.closest('[id$="-modal"]').find('[data-modal-close]').first().trigger('click');
                if (window.Toast) {
                    window.Toast.success(res.message || (window.TRANSLATIONS && window.TRANSLATIONS.action_completed) || 'Action completed.');
                }
                if (typeof onSuccess === 'function') {
                    onSuccess(res);
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                const json = xhr.responseJSON || {};
                const message = typeof json.message === 'string'
                    ? json.message
                    : ((window.TRANSLATIONS && window.TRANSLATIONS.generic_error) || 'An error occurred. Please try again.');

                if (xhr.status === 422 && json.errors) {
                    Object.entries(json.errors).forEach(function ([field, msgs]) {
                        $form.find('[name="' + field + '"]').addClass('is-invalid');
                        $form.find('[data-error="' + field + '"]')
                            .text(msgs[0])
                            .removeClass('hidden');
                    });
                }
                if (window.Toast) {
                    window.Toast.error(message);
                }
            },
        });
    }

    // ── Form bindings ────────────────────────────────────────────────────────

    const orderId = window.ORDER_ID;

    $('#update-status-form').on('submit', function (e) {
        e.preventDefault();
        submitOrderAction('update-status-form', '/orders/' + orderId + '/update-status', function () {
            location.reload();
        });
    });

    $('#refund-form').on('submit', function (e) {
        e.preventDefault();
        submitOrderAction('refund-form', '/orders/' + orderId + '/refund', function () {
            location.reload();
        });
    });

    $('#force-cancel-form').on('submit', function (e) {
        e.preventDefault();
        submitOrderAction('force-cancel-form', '/orders/' + orderId + '/force-cancel', function () {
            location.reload();
        });
    });

    $('#cancel-items-form').on('submit', function (e) {
        e.preventDefault();
        submitOrderAction('cancel-items-form', '/orders/' + orderId + '/cancel-items', function () {
            location.reload();
        });
    });

    $('#dispute-form').on('submit', function (e) {
        e.preventDefault();
        submitOrderAction('dispute-form', '/orders/' + orderId + '/dispute', function () {
            location.reload();
        });
    });

    $('#fraud-form').on('submit', function (e) {
        e.preventDefault();
        submitOrderAction('fraud-form', '/orders/' + orderId + '/flag-fraud', function () {
            location.reload();
        });
    });

    // ── Shipping method assignment ───────────────────────────────────────────

    let selectedShippingMethodId = null;
    let selectedCarrierId = null;
    let assignUrl = null;

    function centsToAmount(cents) {
        return (Number(cents || 0) / 100).toFixed(2);
    }

    function renderShippingMethods(methods) {
        const $container = $('#shipping-assign-methods');
        $container.empty();

        if (!methods || methods.length === 0) {
            const noMethodsText = (window.TRANSLATIONS && window.TRANSLATIONS.no_eligible_shipping_methods) || 'No eligible shipping methods found.';
            $container.append('<p class="text-sm text-gray-400 italic text-center py-4">' + noMethodsText + '</p>');
            return;
        }

        methods.forEach(function (method) {
            const rates = method.rates || [];
            const cheapest = rates.length
                ? rates.reduce((a, b) => (a.base_fee <= b.base_fee ? a : b))
                : null;

            const $card = $('<div>', {
                class: 'shipping-method-card border border-gray-200 rounded-xl p-4 cursor-pointer hover:border-primary-400',
                'data-method-id': method.id,
            });

            $card.append(
                '<div class="flex items-center justify-between mb-2">' +
                '<span class="font-medium text-gray-900">' + method.name + '</span>' +
                '<span class="text-xs text-gray-500">' + method.min_delivery_days + '–' + method.max_delivery_days + ' days</span>' +
                '</div>'
            );

            if (rates.length) {
                const $carrierSelect = $('<select>', { class: 'form-select w-full text-sm shipping-carrier-select' });
                rates.forEach(function (rate) {
                    const label = (rate.carrier_name || (window.TRANSLATIONS && window.TRANSLATIONS.any_carrier) || 'Any carrier') + ' — ' + centsToAmount(rate.base_fee)
                        + (rate.cod_extra_fee ? ' (+' + centsToAmount(rate.cod_extra_fee) + ' COD)' : '');
                    $carrierSelect.append($('<option>', {
                        value: rate.carrier_id || '',
                        text: label,
                        selected: cheapest && rate.carrier_id === cheapest.carrier_id,
                        'data-base-fee': rate.base_fee,
                    }));
                });
                $card.append($carrierSelect);
            } else {
                const noCarrierRateText = (window.TRANSLATIONS && window.TRANSLATIONS.no_carrier_rate_data) || 'No carrier rate data available for this method.';
                $card.append('<p class="text-xs text-gray-400">' + noCarrierRateText + '</p>');
            }

            $card.on('click', function (e) {
                if ($(e.target).is('select, option')) return;
                $('.shipping-method-card').removeClass('border-primary-500 ring-2 ring-primary-200');
                $card.addClass('border-primary-500 ring-2 ring-primary-200');
                selectedShippingMethodId = method.id;
                selectedCarrierId = $card.find('.shipping-carrier-select').val() || null;
                $('#shipping-assign-confirm').prop('disabled', false);
            });

            $card.find('.shipping-carrier-select').on('change', function () {
                if (selectedShippingMethodId === method.id) {
                    selectedCarrierId = $(this).val() || null;
                }
            });

            $container.append($card);
        });
    }

    $(document).on('click', '.btn-assign-shipping', function () {
        const $btn = $(this);
        assignUrl = $btn.data('assign-url');
        const shippingUrl = $btn.data('shipping-url');

        selectedShippingMethodId = null;
        selectedCarrierId = null;
        $('#shipping-assign-confirm').prop('disabled', true);
        $('#shipping-assign-zone-warning').addClass('hidden');
        $('#shipping-assign-error').addClass('hidden').text('');
        $('#shipping-assign-methods').addClass('hidden').empty();
        $('#shipping-assign-loading').removeClass('hidden');

        $('#shipping-assign-modal').modal('open');

        $.ajax({
            url: shippingUrl,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (res) {
                $('#shipping-assign-loading').addClass('hidden');
                $('#shipping-assign-methods').removeClass('hidden');
                if (!res.destination_zone) {
                    $('#shipping-assign-zone-warning').removeClass('hidden');
                }
                renderShippingMethods(res.methods);
            },
            error: function (xhr) {
                $('#shipping-assign-loading').addClass('hidden');
                const json = xhr.responseJSON || {};
                $('#shipping-assign-error').removeClass('hidden').text(json.message || (window.TRANSLATIONS && window.TRANSLATIONS.failed_load_shipping_methods) || 'Failed to load shipping methods.');
            },
        });
    });

    $('#shipping-assign-confirm').on('click', function () {
        if (!selectedShippingMethodId || !assignUrl) return;
        const $btn = $(this);
        $btn.prop('disabled', true);
        $('#shipping-assign-error').addClass('hidden').text('');

        $.ajax({
            url: assignUrl,
            method: 'POST',
            data: {
                shipping_method_id: selectedShippingMethodId,
                carrier_id: selectedCarrierId,
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success: function () {
                $('[data-modal-close]').first().trigger('click');
                if (window.Toast) {
                    window.Toast.success((window.TRANSLATIONS && window.TRANSLATIONS.shipping_method_assigned) || 'Shipping method assigned.');
                }
                location.reload();
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                const json = xhr.responseJSON || {};
                $('#shipping-assign-error').removeClass('hidden').text(json.message || (window.TRANSLATIONS && window.TRANSLATIONS.failed_assign_shipping_method) || 'Failed to assign shipping method.');
            },
        });
    });

});
