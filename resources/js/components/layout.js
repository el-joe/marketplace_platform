/**
 * Admin layout chrome: sidebar collapse, mobile sidebar overlay,
 * modal interactions, flash message auto-dismiss, notification polling,
 * country switcher.
 */
import $ from 'jquery';

$(function () {
    /* ---------- Sidebar collapse ---------- */
    const SIDEBAR_KEY = 'sidebar_collapsed';

    if (localStorage.getItem(SIDEBAR_KEY) === 'true') {
        $('body').addClass('sidebar-collapsed');
    }

    $('#sidebar-toggle').off('click.sidebar').on('click.sidebar', function () {
        $('body').toggleClass('sidebar-collapsed');
        localStorage.setItem(SIDEBAR_KEY, $('body').hasClass('sidebar-collapsed'));
    });

    /* ---------- Mobile sidebar ---------- */
    function openMobileSidebar() { $('body').addClass('sidebar-open'); }
    function closeMobileSidebar() { $('body').removeClass('sidebar-open'); }

    $('#mobile-menu-btn').off('click.sidebar').on('click.sidebar', openMobileSidebar);
    $('#sidebar-backdrop').off('click.sidebar').on('click.sidebar', closeMobileSidebar);

    $(document).off('keydown.sidebar').on('keydown.sidebar', function (e) {
        if (e.key === 'Escape') closeMobileSidebar();
    });

    /* ---------- Modal interactions ---------- */
    $(document).off('click.modal-close').on('click.modal-close', '[data-modal-close]', function () {
        $(this).closest('.modal-backdrop').modal('close');
    });

    // Backdrop click closes (unless persistent)
    $(document).off('mousedown.modal-backdrop').on('mousedown.modal-backdrop', '.modal-backdrop', function (e) {
        if (e.target !== this) return;
        if ($(this).data('persistent')) return;
        $(this).modal('close');
    });

    // Escape key closes top-most open modal
    $(document).off('keydown.modal').on('keydown.modal', function (e) {
        if (e.key !== 'Escape') return;
        const $open = $('.modal-backdrop.flex').last();
        if (!$open.length) return;
        if ($open.data('persistent')) return;
        $open.modal('close');
    });

    /* ---------- Flash message auto-dismiss ---------- */
    $('[data-flash-message]').each(function () {
        const $el = $(this);
        const dismiss = () => $el.fadeOut(200, () => $el.remove());

        setTimeout(dismiss, 5000);
        $el.find('[data-flash-dismiss]').on('click', dismiss);
    });

    /* ---------- Notification polling ---------- */
    function pollNotifications() {
        $.ajax({
            url: '/notifications/unread-count',
            method: 'GET',
        })
            .done(function (response) {
                const count = response && response.data ? response.data.count : 0;
                const $badge = $('#notif-badge');
                if (count > 0) {
                    $badge.text(count > 99 ? '99+' : count).removeClass('hidden');
                } else {
                    $badge.addClass('hidden');
                }
            })
            .fail(function (xhr) {
                // Silently mark handled so global handler stays quiet on dashboards
                xhr.handled = true;
            });
    }

    if ($('#notif-badge').length) {
        pollNotifications();
        window.__notifInterval && clearInterval(window.__notifInterval);
        window.__notifInterval = setInterval(pollNotifications, 60000);
    }

    /* ---------- Load notifications dropdown list on open ---------- */
    $(document).off('click.notif-open').on('click.notif-open', '#topbar [class*="bell"]', function () {
        const $list = $('#notif-list');
        if ($list.data('loaded')) return;

        $.ajax({ url: '/notifications/unread', method: 'GET' })
            .done(function (response) {
                const items = (response && response.data && response.data.items) || [];
                if (!items.length) {
                    $list.html('<div class="p-6 text-center text-sm text-gray-500">No new notifications</div>');
                } else {
                    $list.html(items.map((n) => `
                        <a href="${n.url || '#'}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50">
                            <div class="text-sm font-medium text-gray-900">${$('<div>').text(n.title || '').html()}</div>
                            <div class="text-xs text-gray-500 mt-0.5">${$('<div>').text(n.message || '').html()}</div>
                            <div class="text-[10px] text-gray-400 mt-1">${$('<div>').text(n.created_at || '').html()}</div>
                        </a>
                    `).join(''));
                }
                $list.data('loaded', true);
            })
            .fail(function (xhr) { xhr.handled = true; });
    });

    /* ---------- Mark all notifications read ---------- */
    $(document).off('click.notif-mark').on('click.notif-mark', '#notif-mark-all-read', function (e) {
        e.preventDefault();
        $.ajax({ url: '/notifications/mark-all-read', method: 'POST' })
            .done(function () {
                $('#notif-badge').addClass('hidden');
                $('#notif-list').html('<div class="p-6 text-center text-sm text-gray-500">No new notifications</div>');
                window.Toast && window.Toast.success('All notifications marked as read.');
            });
    });

    /* ---------- Locale / direction switcher ---------- */
    $(document).off('click.locale').on('click.locale', '.locale-switch', function () {
        const locale = $(this).data('locale');
        $.ajax({ url: '/set-locale', method: 'POST', data: { locale } })
            .done(function () { window.location.reload(); })
            .fail(function (xhr) {
                xhr.handled = true;
                window.Toast && window.Toast.error('Could not switch language.');
            });
    });

    /* ---------- Country switcher ---------- */
    $(document).off('click.country').on('click.country', '.country-switch', function () {
        const code = $(this).data('country');
        $.ajax({
            url: '/country',
            method: 'POST',
            data: { country: code },
        })
            .done(function () { window.location.reload(); })
            .fail(function (xhr) {
                if (xhr.status >= 500) return; // global handler shows toast
                xhr.handled = true;
                window.Toast && window.Toast.error('Could not switch country.');
            });
    });

    /* ---------- Global search shortcut (⌘K / Ctrl+K) ---------- */
    $(document).off('keydown.search').on('keydown.search', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            $('#global-search-modal').modal('open');
        }
    });
    $('#global-search-btn').off('click.search').on('click.search', function () {
        $('#global-search-modal').modal('open');
    });
});
