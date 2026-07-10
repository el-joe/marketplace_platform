@extends('layouts.admin')

@section('title', 'FAQs')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" defer></script>
@endpush

@section('content')
<div class="p-6 space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">FAQs</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage the FAQ accordions shown across the public portal, in English and Arabic.</p>
        </div>
        <button type="button" id="btn-new-faq"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
            <x-heroicon name="plus" class="w-4 h-4" />
            New FAQ
        </button>
    </div>

    {{-- Context tabs --}}
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-6">
            @foreach($contexts as $c)
                <a href="{{ route('admin.faqs.index', ['context' => $c]) }}"
                   class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium {{ $context === $c ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ ucwords(str_replace('_', ' ', $c)) }}
                </a>
            @endforeach
        </nav>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-8 px-3 py-3"></th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Question (EN)</th>
                    <th class="px-4 py-3 text-start font-semibold text-gray-700">Question (AR)</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700">Active</th>
                    <th class="px-4 py-3 text-end font-semibold text-gray-700">Actions</th>
                </tr>
            </thead>
            <tbody id="faq-list" class="divide-y divide-gray-100">
                @forelse($faqs as $faq)
                    <tr class="hover:bg-gray-50 faq-row" data-id="{{ $faq->id }}">
                        <td class="px-3 py-3 text-gray-300 cursor-grab drag-handle">
                            <x-heroicon name="bars-3" class="w-4 h-4" />
                        </td>
                        <td class="px-4 py-3 max-w-sm truncate font-medium text-gray-900">{{ $faq->question_en }}</td>
                        <td class="px-4 py-3 max-w-sm truncate text-gray-600" dir="rtl">{{ $faq->question_ar }}</td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" dir="ltr"
                                    class="btn-toggle-active relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none {{ $faq->is_active ? 'bg-emerald-500' : 'bg-gray-200' }}"
                                    data-id="{{ $faq->id }}" aria-label="Toggle active">
                                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform {{ $faq->is_active ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-end whitespace-nowrap">
                            <button type="button" class="btn-edit-faq text-xs text-primary-600 font-medium hover:underline"
                                    data-faq="{{ json_encode($faq->only(['id','context','question_en','question_ar','answer_en','answer_ar','sort_order','is_active'])) }}">
                                Edit
                            </button>
                            <button type="button" class="btn-delete-faq ms-2 text-xs text-red-500 hover:underline"
                                    data-id="{{ $faq->id }}" data-name="{{ $faq->question_en }}">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-gray-400">No FAQs found for this context yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create / Edit modal --}}
<x-modal id="faq-modal" title="FAQ" size="lg">
    <form id="faq-form" class="space-y-4">
        @csrf
        <input type="hidden" id="form-faq-id" value="">

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Context</label>
            <select name="context" id="f-context" class="form-input w-full text-sm">
                @foreach($contexts as $c)
                    <option value="{{ $c }}" {{ $c === $context ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $c)) }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Question (English) <span class="text-red-500">*</span></label>
                <input type="text" id="f-question-en" required maxlength="500" class="form-input w-full text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Question (Arabic) <span class="text-red-500">*</span></label>
                <input type="text" id="f-question-ar" required maxlength="500" dir="rtl" class="form-input w-full text-sm">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Answer (English) <span class="text-red-500">*</span></label>
                <textarea id="f-answer-en" required rows="5" class="form-input w-full text-sm"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Answer (Arabic) <span class="text-red-500">*</span></label>
                <textarea id="f-answer-ar" required rows="5" dir="rtl" class="form-input w-full text-sm"></textarea>
            </div>
        </div>

        <div class="flex items-center gap-6">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Sort order</label>
                <input type="number" id="f-sort-order" value="0" min="0" class="form-input w-28 text-sm">
            </div>
            <label class="flex items-center gap-2 cursor-pointer select-none mt-4">
                <input type="checkbox" id="f-is-active" class="rounded text-primary-600" checked>
                <span class="text-sm text-gray-700">Active</span>
            </label>
        </div>

        <div id="form-error" class="hidden rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></div>
    </form>

    <x-slot:footer>
        <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
        <button type="button" id="btn-save-faq" class="btn btn-primary">Save</button>
    </x-slot:footer>
</x-modal>

{{-- Delete confirmation modal --}}
<x-modal id="delete-modal" title="Delete FAQ" size="sm">
    <p class="text-sm text-gray-700">Are you sure you want to delete "<strong id="delete-faq-name"></strong>"?</p>
    <input type="hidden" id="delete-faq-id">
    <div id="delete-error" class="hidden mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></div>
    <x-slot:footer>
        <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
        <button type="button" id="btn-confirm-delete" class="btn btn-danger">Delete</button>
    </x-slot:footer>
</x-modal>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const base = '{{ rtrim(route("admin.faqs.index"), "/") }}';

    function openModal(id) { document.getElementById(id)?.classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id)?.classList.add('hidden'); }

    document.querySelectorAll('[data-modal-close]').forEach(btn =>
        btn.addEventListener('click', () => btn.closest('[id]')?.classList.add('hidden'))
    );

    async function req(url, method, body) {
        const res = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await res.json().catch(() => ({}));
        return { ok: res.ok, data };
    }

    function resetForm() {
        document.getElementById('form-faq-id').value = '';
        ['f-question-en', 'f-question-ar', 'f-answer-en', 'f-answer-ar'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.getElementById('f-context').value = '{{ $context }}';
        document.getElementById('f-sort-order').value = '0';
        document.getElementById('f-is-active').checked = true;
        document.getElementById('form-error').classList.add('hidden');
    }

    document.getElementById('btn-new-faq').addEventListener('click', () => {
        resetForm();
        document.querySelector('#faq-modal [id$="-title"]').textContent = 'New FAQ';
        openModal('faq-modal');
    });

    document.querySelectorAll('.btn-edit-faq').forEach(btn => {
        btn.addEventListener('click', () => {
            const faq = JSON.parse(btn.dataset.faq);
            document.getElementById('form-faq-id').value = faq.id;
            document.getElementById('f-context').value = faq.context;
            document.getElementById('f-question-en').value = faq.question_en;
            document.getElementById('f-question-ar').value = faq.question_ar;
            document.getElementById('f-answer-en').value = faq.answer_en;
            document.getElementById('f-answer-ar').value = faq.answer_ar;
            document.getElementById('f-sort-order').value = faq.sort_order ?? 0;
            document.getElementById('f-is-active').checked = !!faq.is_active;
            document.querySelector('#faq-modal [id$="-title"]').textContent = 'Edit FAQ';
            document.getElementById('form-error').classList.add('hidden');
            openModal('faq-modal');
        });
    });

    document.getElementById('btn-save-faq').addEventListener('click', async () => {
        const id = document.getElementById('form-faq-id').value;
        const payload = {
            context: document.getElementById('f-context').value,
            question_en: document.getElementById('f-question-en').value,
            question_ar: document.getElementById('f-question-ar').value,
            answer_en: document.getElementById('f-answer-en').value,
            answer_ar: document.getElementById('f-answer-ar').value,
            sort_order: parseInt(document.getElementById('f-sort-order').value) || 0,
            is_active: document.getElementById('f-is-active').checked ? 1 : 0,
        };

        const url = id ? `${base}/${id}` : base;
        const method = id ? 'PUT' : 'POST';
        const errEl = document.getElementById('form-error');

        const { ok, data } = await req(url, method, payload);
        if (ok) {
            closeModal('faq-modal');
            window.location.href = `${base}?context=${payload.context}`;
        } else {
            errEl.textContent = data.message ?? Object.values(data.errors ?? {}).flat().join(' ');
            errEl.classList.remove('hidden');
        }
    });

    document.querySelectorAll('.btn-delete-faq').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete-faq-name').textContent = btn.dataset.name;
            document.getElementById('delete-faq-id').value = btn.dataset.id;
            document.getElementById('delete-error').classList.add('hidden');
            openModal('delete-modal');
        });
    });

    document.getElementById('btn-confirm-delete').addEventListener('click', async () => {
        const id = document.getElementById('delete-faq-id').value;
        const { ok, data } = await req(`${base}/${id}`, 'DELETE');
        if (ok) {
            closeModal('delete-modal');
            window.location.reload();
        } else {
            const errEl = document.getElementById('delete-error');
            errEl.textContent = data.message ?? 'Could not delete FAQ.';
            errEl.classList.remove('hidden');
        }
    });

    document.querySelectorAll('.btn-toggle-active').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const { ok, data } = await req(`${base}/${id}/toggle`, 'POST');
            if (ok) {
                const active = data.is_active;
                btn.classList.toggle('bg-emerald-500', active);
                btn.classList.toggle('bg-gray-200', !active);
                const dot = btn.querySelector('span');
                dot.classList.toggle('translate-x-4', active);
                dot.classList.toggle('translate-x-0.5', !active);
            }
        });
    });

    const tbody = document.getElementById('faq-list');
    if (tbody && window.Sortable) {
        Sortable.create(tbody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: async () => {
                const ids = Array.from(document.querySelectorAll('.faq-row')).map(r => r.dataset.id);
                await req('{{ route("admin.faqs.reorder") }}', 'POST', { ids });
            },
        });
    }
})();
</script>
@endpush
@endsection
