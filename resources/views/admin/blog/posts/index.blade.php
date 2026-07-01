@extends('layouts.admin')

@push('styles')
    @vite(['resources/js/components/datatable.js'])
@endpush

@section('title', 'Blog Posts')

@section('content')
<div class="p-6 space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Blog Posts</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage all blog content across country portals.</p>
        </div>
        <a href="{{ route('admin.blog.posts.create') }}"
           class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
            <x-heroicon name="plus" class="w-4 h-4" />
            New Post
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <x-stat-card title="Published"    :value="number_format($stats['published'])" icon="check-circle"   iconBg="bg-emerald-100 text-emerald-600" />
        <x-stat-card title="Draft"        :value="number_format($stats['draft'])"     icon="pencil"         iconBg="bg-gray-100 text-gray-600" />
        <x-stat-card title="Scheduled"    :value="number_format($stats['scheduled'])" icon="clock"          iconBg="bg-blue-100 text-blue-600" />
        <x-stat-card title="Archived"     :value="number_format($stats['archived'])"  icon="archive-box"    iconBg="bg-amber-100 text-amber-600" />
        <x-stat-card title="Views (Month" :value="number_format($stats['views_month'])" icon="eye"          iconBg="bg-primary-100 text-primary-600" />
    </div>

    {{-- Filter bar --}}
    <x-card>
        <form id="posts-filter-form" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" id="search-input" class="form-input w-full text-sm" placeholder="Title…">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                <select id="filter-status" class="form-input w-full text-sm">
                    <option value="">All statuses</option>
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                <select id="filter-category" class="form-input w-full text-sm">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <optgroup label="{{ $cat->name_en }}">
                            <option value="{{ $cat->id }}">{{ $cat->name_en }}</option>
                            @foreach($cat->children as $child)
                                <option value="{{ $child->id }}">↳ {{ $child->name_en }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
                <select id="filter-country" class="form-input w-full text-sm">
                    <option value="">All countries</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}">{{ $c->flag_emoji ? $c->flag_emoji . ' ' : '' }}{{ $c->name_en }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-600 mb-1">Author</label>
                <select id="filter-author" class="form-input w-full text-sm">
                    <option value="">All authors</option>
                    @foreach($authors as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Published from</label>
                <input type="date" id="filter-date-from" class="form-input w-full text-sm">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-600 mb-1">Published to</label>
                <input type="date" id="filter-date-to" class="form-input w-full text-sm">
            </div>
            <button type="button" id="clear-filters" class="btn btn-ghost btn-sm self-end">Reset</button>
        </form>
    </x-card>

    {{-- DataTable --}}
    <x-card>
        <div class="overflow-x-auto">
            <table id="posts-table" class="w-full text-sm" style="width:100%">
                <thead>
                    <tr class="border-b border-gray-100 text-left">
                        <th class="py-2 pr-3 text-xs font-medium text-gray-500 uppercase w-12"></th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Author</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Country</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Published</th>
                        <th class="py-2 pr-4 text-xs font-medium text-gray-500 uppercase">Views</th>
                        <th class="py-2 text-xs font-medium text-gray-500 uppercase text-right">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>

</div>

{{-- Delete confirmation modal --}}
<x-modal id="delete-post-modal" title="Delete Post" size="sm">
    <p class="text-sm text-gray-700">Soft-delete this post? It can be restored from the database.</p>
    <input type="hidden" id="delete-post-id">
    <div id="delete-post-error" class="hidden mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></div>
    <x-slot:footer>
        <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
        <button type="button" id="btn-confirm-delete-post" class="btn btn-danger">Delete</button>
    </x-slot:footer>
</x-modal>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    async function req(url, method) {
        const res = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        return { ok: res.ok, data: await res.json().catch(() => ({})) };
    }

    // ── Init DataTable ────────────────────────────────────────────────────────
    function getFilters() {
        return {
            search:           { value: document.getElementById('search-input').value },
            status:           document.getElementById('filter-status').value,
            blog_category_id: document.getElementById('filter-category').value,
            country_id:       document.getElementById('filter-country').value,
            author_admin_id:  document.getElementById('filter-author').value,
            date_from:        document.getElementById('filter-date-from').value,
            date_to:          document.getElementById('filter-date-to').value,
        };
    }

    let dtInstance = null;

    function initTable() {
        if (typeof window.initDataTable !== 'function') {
            // fallback — raw jQuery DataTables
            dtInstance = window.$('#posts-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("admin.blog.posts.datatable") }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF },
                    data: function (d) {
                        const f = getFilters();
                        d.status           = f.status;
                        d.blog_category_id = f.blog_category_id;
                        d.country_id       = f.country_id;
                        d.author_admin_id  = f.author_admin_id;
                        d.date_from        = f.date_from;
                        d.date_to          = f.date_to;
                        if (f.search.value) d.search = f.search;
                    },
                },
                columns: [
                    { data: 'image',   orderable: false },
                    { data: 'title',   orderable: true },
                    { data: 'author',  orderable: false },
                    { data: 'country', orderable: false },
                    { data: 'status',  orderable: true },
                    { data: 'date',    orderable: true },
                    { data: 'views',   orderable: true },
                    { data: 'actions', orderable: false, className: 'text-right' },
                ],
                order: [[5, 'desc']],
                pageLength: 25,
                language: { processing: 'Loading…', emptyTable: 'No posts found.' },
            });
        } else {
            dtInstance = window.initDataTable('posts-table', {
                url: '{{ route("admin.blog.posts.datatable") }}',
                columns: [
                    { data: 'image',   orderable: false },
                    { data: 'title',   orderable: true },
                    { data: 'author',  orderable: false },
                    { data: 'country', orderable: false },
                    { data: 'status',  orderable: true },
                    { data: 'date',    orderable: true },
                    { data: 'views',   orderable: true },
                    { data: 'actions', orderable: false },
                ],
                order: [[5, 'desc']],
                pageLength: 25,
                extraData: getFilters,
            });
        }
    }

    // Wait for DT to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTable);
    } else {
        initTable();
    }

    // ── Filters ───────────────────────────────────────────────────────────────
    ['filter-status','filter-category','filter-country','filter-author','filter-date-from','filter-date-to'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', () => dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.());
    });
    document.getElementById('search-input')?.addEventListener('input', function () {
        clearTimeout(this._t);
        this._t = setTimeout(() => dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.(), 400);
    });
    document.getElementById('clear-filters')?.addEventListener('click', () => {
        ['filter-status','filter-category','filter-country','filter-author'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        ['filter-date-from','filter-date-to','search-input'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.();
    });

    // ── Table action delegation ───────────────────────────────────────────────
    document.getElementById('posts-table').addEventListener('click', async (e) => {
        const featBtn    = e.target.closest('.btn-feature');
        const archiveBtn = e.target.closest('.btn-archive');
        const deleteBtn  = e.target.closest('.btn-delete');

        if (featBtn) {
            const { ok } = await req(featBtn.dataset.url, 'POST');
            if (ok) dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.();
        }

        if (archiveBtn) {
            if (!confirm('Archive this post?')) return;
            const { ok } = await req(archiveBtn.dataset.url, 'POST');
            if (ok) dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.();
        }

        if (deleteBtn) {
            document.getElementById('delete-post-id').value = deleteBtn.dataset.id;
            document.getElementById('delete-post-error').classList.add('hidden');
            document.getElementById('delete-post-modal')?.classList.remove('hidden');
            window._deletPostUrl = deleteBtn.dataset.url;
        }
    });

    document.getElementById('btn-confirm-delete-post')?.addEventListener('click', async () => {
        const url = window._deletPostUrl;
        if (!url) return;
        const { ok, data } = await req(url, 'DELETE');
        if (ok) {
            document.getElementById('delete-post-modal')?.classList.add('hidden');
            dtInstance?.ajax?.reload?.() ?? dtInstance?.draw?.();
        } else {
            const errEl = document.getElementById('delete-post-error');
            errEl.textContent = data.message ?? 'Could not delete.';
            errEl.classList.remove('hidden');
        }
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn =>
        btn.addEventListener('click', () => btn.closest('[id]')?.classList.add('hidden'))
    );
})();
</script>
@endpush
@endsection
