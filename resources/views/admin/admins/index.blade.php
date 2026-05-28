@extends('layouts.admin')

@section('title', 'Administrators')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js', 'resources/js/admin/admins.js'])
@endpush

@section('content')
    {{-- ── Impersonation Banner ─────────────────────────────────────────── --}}
    @if(session('impersonating_original_id'))
        <div
            class="sticky top-0 z-50 bg-amber-500 text-white px-4 py-2 flex items-center justify-between shadow-sm mb-4 rounded-lg">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                <span class="text-sm font-medium">
                    You are impersonating
                    <strong>{{ auth('admin')->user()->name }}</strong>.
                    All actions are performed as this user.
                </span>
            </div>
            <form method="POST" action="{{ route('admin.admins.stop-impersonating') }}">
                @csrf
                <button type="submit" class="btn btn-xs bg-white text-amber-700 hover:bg-amber-50 font-semibold">
                    Stop Impersonating
                </button>
            </form>
        </div>
    @endif

    @php
        $columns = [
            ['title' => 'Name', 'data' => 'name', 'name' => 'name'],
            ['title' => 'Email', 'data' => 'email', 'name' => 'email', 'searchable' => false],
            ['title' => 'Country', 'data' => 'country_name', 'name' => 'country_name', 'searchable' => false, 'orderable' => false],
            [
                'title' => 'Roles',
                'data' => 'roles',
                'name' => 'roles',
                'searchable' => false,
                'orderable' => false,
                'render' => 'function(data) {
                            if (!data || !data.length) return "<span class=\"text-gray-400 text-xs\">—</span>";
                            return data.map(function(r) {
                                return "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700\">" + r + "</span>";
                            }).join(" ");
                        }',
            ],
            [
                'title' => 'Status',
                'data' => 'status',
                'name' => 'status',
                'searchable' => false,
                'render' => 'Renderers.badge({
                            active:   { label: "Active",   color: "success" },
                            inactive: { label: "Inactive", color: "gray"    }
                        })',
            ],
            [
                'title' => 'Last Login',
                'data' => 'last_login_at',
                'name' => 'last_login_at',
                'searchable' => false,
                'render' => 'Renderers.dateAgo',
            ],
            [
                'title' => 'Created',
                'data' => 'created_at',
                'name' => 'created_at',
                'searchable' => false,
                'render' => 'Renderers.date',
            ],
            [
                'title' => '',
                'data' => 'actions',
                'name' => 'actions',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-right',
                'render' => 'Renderers.actions([
                            { type: "link",   label: "Edit",        url: ":edit_url",   class: "btn-secondary" },
                            { type: "button", label: "Sessions",    id: "sessions",     class: "btn-ghost" },
                            { type: "button", label: "Reset Pwd",   id: "reset-pwd",    class: "btn-ghost" },
                            { type: "button", label: "Impersonate", id: "impersonate",  class: "btn-warning",   condition: (row) => row.can_impersonate },
                            { type: "button", label: "Deactivate",  id: "deactivate",   class: "btn-secondary", condition: (row) => !row.is_self && row.status === "active" },
                            { type: "button", label: "Activate",    id: "activate",     class: "btn-success",   condition: (row) => !row.is_self && row.status === "inactive" },
                            { type: "button", label: "Delete",      id: "delete",       class: "btn-danger",    condition: (row) => row.can_delete },
                        ])',
            ],
        ];

        $filters = [
            ['type' => 'text', 'name' => 'search', 'label' => 'Name / Email', 'placeholder' => 'Search…'],
            [
                'type' => 'select',
                'name' => 'status',
                'label' => 'Status',
                'options' => ['' => 'All Statuses', 'active' => 'Active', 'inactive' => 'Inactive'],
            ],
            [
                'type' => 'select',
                'name' => 'role',
                'label' => 'Role',
                'options' => ['' => 'All Roles'] + $roles->pluck('name', 'name')->toArray(),
            ],
            [
                'type' => 'select',
                'name' => 'country_id',
                'label' => 'Country',
                'options' => ['' => 'All Countries'] + $countries->pluck('name_en', 'id')->toArray(),
            ],
        ];
    @endphp

    <x-table.datatable id="admins-table" url="{{ route('admin.admins.datatable') }}" :columns="$columns" :filters="$filters"
        :create-action="['url' => route('admin.admins.create'), 'label' => 'Add Administrator']" :page-length="25"
        :order="[[6, 'desc']]" />

    {{-- ── Login Sessions Modal ──────────────────────────────────────────── --}}
    <div id="sessions-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true" role="dialog">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" id="sessions-backdrop"></div>
            <div
                class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900" id="sessions-modal-title">Login Sessions</h3>
                    <button type="button" id="sessions-modal-close" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-4 max-h-96 overflow-y-auto">
                    <div id="sessions-loading" class="text-center py-8 text-gray-500 text-sm">Loading…</div>
                    <div id="sessions-list" class="hidden space-y-2"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.tableActions = window.tableActions || {};

        // ── Delete ─────────────────────────────────────────────────────────────────
        window.tableActions.delete = async function (id, row) {
            const confirmed = window.confirmDelete
                ? await window.confirmDelete('Delete administrator "' + row.name + '"?', { title: 'Delete Administrator?' })
                : confirm('Delete "' + row.name + '"?');
            if (!confirmed) return;

            $.ajax({
                url: row.delete_url, method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
                .done(() => { window.Toast?.success('Administrator deleted.'); window.reloadDataTable('admins-table'); })
                .fail(xhr => { window.Toast?.error(xhr.responseJSON?.message || 'Delete failed.'); });
        };

        // ── Deactivate ─────────────────────────────────────────────────────────────
        window.tableActions.deactivate = async function (id, row) {
            const confirmed = window.confirmBulkAction
                ? await window.confirmBulkAction('Deactivate "' + row.name + '"?', { title: 'Deactivate Administrator?' })
                : confirm('Deactivate "' + row.name + '"?');
            if (!confirmed) return;

            $.ajax({
                url: row.toggle_url, method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
                .done(() => { window.Toast?.success('Administrator deactivated.'); window.reloadDataTable('admins-table'); })
                .fail(xhr => { window.Toast?.error(xhr.responseJSON?.message || 'Action failed.'); });
        };

        // ── Activate ───────────────────────────────────────────────────────────────
        window.tableActions.activate = async function (id, row) {
            $.ajax({
                url: row.toggle_url, method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
                .done(() => { window.Toast?.success('Administrator activated.'); window.reloadDataTable('admins-table'); })
                .fail(xhr => { window.Toast?.error(xhr.responseJSON?.message || 'Action failed.'); });
        };

        // ── Reset Password ─────────────────────────────────────────────────────────
        window.tableActions['reset-pwd'] = async function (id, row) {
            const confirmed = window.confirmBulkAction
                ? await window.confirmBulkAction('Reset password for "' + row.name + '"? They will receive a new password by email.', { title: 'Reset Password?' })
                : confirm('Reset password for "' + row.name + '"?');
            if (!confirmed) return;

            $.ajax({
                url: row.reset_pwd_url, method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
                .done(data => { window.Toast?.success(data.message || 'Password reset.'); })
                .fail(xhr => { window.Toast?.error(xhr.responseJSON?.message || 'Reset failed.'); });
        };

        // ── Impersonate ────────────────────────────────────────────────────────────
        window.tableActions.impersonate = async function (id, row) {
            const confirmed = window.confirmBulkAction
                ? await window.confirmBulkAction('You will act as ' + row.name + '. All actions will be performed as this user.', { title: 'Impersonate Administrator?' })
                : confirm('Impersonate "' + row.name + '"?');
            if (!confirmed) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = row.impersonate_url;
            form.innerHTML = '<input type="hidden" name="_token" value="' + document.querySelector('meta[name="csrf-token"]').content + '">';
            document.body.appendChild(form);
            form.submit();
        };

        // ── Login Sessions Modal ────────────────────────────────────────────────────
        window.tableActions.sessions = function (id, row) {
            const modal = document.getElementById('sessions-modal');
            const title = document.getElementById('sessions-modal-title');
            const loading = document.getElementById('sessions-loading');
            const list = document.getElementById('sessions-list');

            title.textContent = 'Login Sessions — ' + row.name;
            loading.classList.remove('hidden');
            list.classList.add('hidden');
            list.innerHTML = '';
            modal.classList.remove('hidden');

            $.get(row.sessions_url)
                .done(function (data) {
                    loading.classList.add('hidden');
                    if (!data.data || !data.data.length) {
                        list.innerHTML = '<p class="text-sm text-gray-500 py-4 text-center">No sessions found.</p>';
                    } else {
                        list.innerHTML = data.data.map(function (s) {
                            const impersonationBadge = s.is_impersonation
                                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 ml-2">Impersonation</span>'
                                : '';
                            return '<div class="border border-gray-200 rounded-lg px-4 py-3 text-sm">'
                                + '<div class="flex items-start justify-between gap-2">'
                                + '<div>'
                                + '<span class="font-medium text-gray-900">' + (s.ip_address || '—') + '</span>'
                                + impersonationBadge
                                + '<p class="text-xs text-gray-500 mt-0.5 truncate max-w-xs" title="' + s.user_agent + '">' + (s.user_agent || '—') + '</p>'
                                + '</div>'
                                + '<div class="text-right shrink-0">'
                                + '<p class="text-xs text-gray-500">' + (s.started_at ? new Date(s.started_at).toLocaleString() : '—') + '</p>'
                                + (s.duration ? '<p class="text-xs text-gray-400">' + s.duration + '</p>' : '')
                                + '</div>'
                                + '</div>'
                                + '</div>';
                        }).join('');
                    }
                    list.classList.remove('hidden');
                })
                .fail(function () {
                    loading.textContent = 'Failed to load sessions.';
                });
        };

        document.getElementById('sessions-modal-close').addEventListener('click', () => {
            document.getElementById('sessions-modal').classList.add('hidden');
        });
        document.getElementById('sessions-backdrop').addEventListener('click', () => {
            document.getElementById('sessions-modal').classList.add('hidden');
        });
    </script>
@endpush