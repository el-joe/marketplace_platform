import $ from 'jquery';

$(function () {

    // ── Sub-order accordion ──────────────────────────────────────────────────

    $(document).on('click', '.sub-order-toggle, .sub-order-header', function (e) {
        // Don't toggle if user clicked a button inside header (other than toggle)
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

    // ── Load next statuses dynamically ──────────────────────────────────────

    $('#sub-order-select').on('change', function () {
        const subOrderId = $(this).val();
        const $newStatus = $('#new-status-select');

        $newStatus.empty().append('<option value="">Loading…</option>').prop('disabled', true);

        if (!subOrderId) {
            $newStatus.empty().append('<option value="">Select sub-order first…</option>');
            return;
        }

        $.get('/sub-orders/' + subOrderId + '/next-statuses')
            .done(function (res) {
                $newStatus.empty().append('<option value="">Select new status…</option>');
                (res.data || []).forEach(function (s) {
                    $newStatus.append(new Option(s.label, s.value));
                });
            })
            .fail(function () {
                $newStatus.empty().append('<option value="">Failed to load statuses</option>');
            })
            .always(function () {
                $newStatus.prop('disabled', false);
            });
    });

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
                // Close nearest modal
                $form.closest('[id$="-modal"]').find('[data-modal-close]').first().trigger('click');
                if (window.Toast) {
                    window.Toast.success(res.message || 'Action completed.');
                }
                if (typeof onSuccess === 'function') {
                    onSuccess(res);
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                const json = xhr.responseJSON || {};

                if (xhr.status === 422 && json.errors) {
                    Object.entries(json.errors).forEach(function ([field, msgs]) {
                        $form.find('[name="' + field + '"]').addClass('is-invalid');
                        $form.find('[data-error="' + field + '"]')
                            .text(msgs[0])
                            .removeClass('hidden');
                    });
                } else {
                    if (window.Toast) {
                        window.Toast.error(json.message || 'An error occurred. Please try again.');
                    }
                }
            },
        });
    }

    // ── Form bindings ────────────────────────────────────────────────────────

    const orderId = window.ORDER_ID;

    $('#update-status-form').on('submit', function (e) {
        e.preventDefault();
        submitOrderAction('update-status-form', '/orders/update-sub-order-status', function () {
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

});
