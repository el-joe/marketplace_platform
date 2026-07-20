@extends('layouts.admin')

@section('title', 'Packaging Catalog')

@push('styles')
    @vite(['resources/js/components/datatable.js', 'resources/js/components/column-renderers.js'])
@endpush

@section('content')
<div class="p-6 space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Packaging Catalog</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage packaging supply items available to vendors.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.packaging.requests') }}" class="btn btn-secondary text-sm">View Orders</a>
            <button type="button" id="btn-new-item" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700">
                <x-heroicon name="plus" class="w-4 h-4" />
                Add Item
            </button>
        </div>
    </div>

    {{-- DataTable --}}
    @php
        $columns = [
            ['title' => '', 'data' => 'image', 'name' => 'image', 'orderable' => false, 'searchable' => false],
            ['title' => 'Name', 'data' => 'name', 'name' => 'name_en'],
            ['title' => 'Type', 'data' => 'type', 'name' => 'type', 'searchable' => false],
            ['title' => 'Size', 'data' => 'size', 'name' => 'size', 'searchable' => false],
            ['title' => 'Unit Cost', 'data' => 'unit_cost', 'name' => 'unit_cost', 'searchable' => false],
            ['title' => 'Stock', 'data' => 'stock', 'name' => 'stock', 'searchable' => false],
            ['title' => 'Active', 'data' => 'status', 'name' => 'status', 'searchable' => false],
            ['title' => '', 'data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false, 'className' => 'text-end'],
        ];

        $filters = [
            [
                'type' => 'select',
                'name' => 'type',
                'label' => 'Type',
                'options' => [
                    'box' => 'Box', 'bag' => 'Bag', 'tape' => 'Tape',
                    'label' => 'Label', 'bubble_wrap' => 'Bubble Wrap', 'other' => 'Other',
                ],
            ],
            [
                'type' => 'select',
                'name' => 'is_active',
                'label' => 'Active',
                'options' => ['1' => 'Active', '0' => 'Inactive'],
            ],
        ];
    @endphp

    <x-table.datatable id="packaging-catalog-table" url="{{ route('admin.packaging.catalog.datatable') }}"
        :columns="$columns" :filters="$filters" :page-length="25" />
</div>

{{-- Add / Edit Modal --}}
<x-modal id="item-modal" title="Add Packaging Item" size="lg">
    <form id="item-form" class="space-y-4" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="f-id" value="">

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Name (English) <span class="text-red-500">*</span></label>
                <input type="text" id="f-name-en" required maxlength="255" class="form-input w-full text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Name (Arabic) <span class="text-red-500">*</span></label>
                <input type="text" id="f-name-ar" required maxlength="255" dir="rtl" class="form-input w-full text-sm">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Type <span class="text-red-500">*</span></label>
                <select id="f-type" required class="form-select w-full text-sm">
                    <option value="box">Box</option>
                    <option value="bag">Bag</option>
                    <option value="tape">Tape</option>
                    <option value="label">Label</option>
                    <option value="bubble_wrap">Bubble Wrap</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Size</label>
                <input type="text" id="f-size" maxlength="100" class="form-input w-full text-sm" placeholder="e.g. 30x20x15 cm">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Description (English)</label>
                <textarea id="f-description-en" rows="2" class="form-input w-full text-sm"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Description (Arabic)</label>
                <textarea id="f-description-ar" rows="2" dir="rtl" class="form-input w-full text-sm"></textarea>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Unit Cost (cents) <span class="text-red-500">*</span></label>
                <input type="number" id="f-unit-cost" required min="1" class="form-input w-full text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Currency <span class="text-red-500">*</span></label>
                <select id="f-currency" required class="form-select w-full text-sm">
                    @foreach(['AED','SAR','EGP','KWD','OMR','QAR','BHD','JOD'] as $c)
                        <option value="{{ $c }}" @selected($c === 'SAR')>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Sort Order</label>
                <input type="number" id="f-sort-order" value="0" min="0" class="form-input w-full text-sm">
            </div>
        </div>

        <div class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-700 mb-1">Stock Available</label>
                <input type="number" id="f-stock" min="0" class="form-input w-full text-sm">
            </div>
            <label class="flex items-center gap-2 cursor-pointer select-none mb-2">
                <input type="checkbox" id="f-unlimited-stock" class="rounded text-primary-600">
                <span class="text-sm text-gray-700">Unlimited</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none mb-2">
                <input type="checkbox" id="f-is-active" checked class="rounded text-primary-600">
                <span class="text-sm text-gray-700">Active</span>
            </label>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Image</label>
            <input type="file" id="f-image" accept="image/jpeg,image/png,image/jpg,image/webp" class="form-input w-full text-sm">
            <img id="f-image-preview" src="" class="hidden mt-2 w-20 h-20 object-cover rounded-lg border border-gray-200">
        </div>

        <div id="form-error" class="hidden rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></div>
    </form>

    <x-slot:footer>
        <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
        <button type="button" id="btn-save-item" class="btn btn-primary">Save</button>
    </x-slot:footer>
</x-modal>

{{-- Delete confirmation modal --}}
<x-modal id="delete-item-modal" title="Delete Packaging Item" size="sm">
    <p class="text-sm text-gray-700">Are you sure you want to delete "<strong id="delete-item-name"></strong>"?</p>
    <input type="hidden" id="delete-item-id">
    <div id="delete-item-error" class="hidden mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"></div>
    <x-slot:footer>
        <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
        <button type="button" id="btn-confirm-delete-item" class="btn btn-danger">Delete</button>
    </x-slot:footer>
</x-modal>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const storeUrl = '{{ route('admin.packaging.catalog.store') }}';

    function openModal(id) { window.jQuery ? window.jQuery('#' + id).modal('open') : document.getElementById(id)?.classList.remove('hidden'); }
    function closeModal(id) { window.jQuery ? window.jQuery('#' + id).modal('close') : document.getElementById(id)?.classList.add('hidden'); }

    function resetForm() {
        document.getElementById('f-id').value = '';
        ['f-name-en', 'f-name-ar', 'f-size', 'f-description-en', 'f-description-ar', 'f-image'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.getElementById('f-type').value = 'box';
        document.getElementById('f-currency').value = 'SAR';
        document.getElementById('f-unit-cost').value = '';
        document.getElementById('f-stock').value = '';
        document.getElementById('f-unlimited-stock').checked = false;
        document.getElementById('f-sort-order').value = '0';
        document.getElementById('f-is-active').checked = true;
        document.getElementById('f-image-preview').classList.add('hidden');
        document.getElementById('form-error').classList.add('hidden');
    }

    document.getElementById('f-unlimited-stock').addEventListener('change', function () {
        const stockInput = document.getElementById('f-stock');
        stockInput.disabled = this.checked;
        if (this.checked) stockInput.value = '';
    });

    document.getElementById('f-image').addEventListener('change', function () {
        const file = this.files[0];
        const preview = document.getElementById('f-image-preview');
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('hidden');
        }
    });

    document.getElementById('btn-new-item').addEventListener('click', () => {
        resetForm();
        document.querySelector('#item-modal [id$="-title"]').textContent = 'Add Packaging Item';
        openModal('item-modal');
    });

    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.js-edit-item');
        if (editBtn) {
            const item = JSON.parse(editBtn.dataset.item);
            resetForm();
            document.getElementById('f-id').value = item.id;
            document.getElementById('f-name-en').value = item.name_en ?? '';
            document.getElementById('f-name-ar').value = item.name_ar ?? '';
            document.getElementById('f-type').value = item.type ?? 'box';
            document.getElementById('f-size').value = item.size ?? '';
            document.getElementById('f-description-en').value = item.description_en ?? '';
            document.getElementById('f-description-ar').value = item.description_ar ?? '';
            document.getElementById('f-unit-cost').value = item.unit_cost ?? '';
            document.getElementById('f-currency').value = item.currency ?? 'SAR';
            document.getElementById('f-sort-order').value = item.sort_order ?? 0;
            document.getElementById('f-is-active').checked = !!item.is_active;
            if (item.stock_available === null || item.stock_available === undefined) {
                document.getElementById('f-unlimited-stock').checked = true;
                document.getElementById('f-stock').disabled = true;
            } else {
                document.getElementById('f-stock').value = item.stock_available;
            }
            if (item.image_path) {
                const preview = document.getElementById('f-image-preview');
                preview.src = '/storage/' + item.image_path;
                preview.classList.remove('hidden');
            }
            document.querySelector('#item-modal [id$="-title"]').textContent = 'Edit Packaging Item';
            openModal('item-modal');
        }

        const deleteBtn = e.target.closest('.js-delete-item');
        if (deleteBtn) {
            document.getElementById('delete-item-name').textContent = deleteBtn.dataset.name;
            document.getElementById('delete-item-id').value = deleteBtn.dataset.id;
            document.getElementById('delete-item-error').classList.add('hidden');
            openModal('delete-item-modal');
        }

        const toggleBtn = e.target.closest('.js-toggle-active');
        if (toggleBtn) {
            const id = toggleBtn.dataset.id;
            fetch(`{{ url('admin/packaging/catalog') }}/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    const active = data.is_active;
                    toggleBtn.classList.toggle('bg-emerald-500', active);
                    toggleBtn.classList.toggle('bg-gray-200', !active);
                    const dot = toggleBtn.querySelector('span');
                    dot.classList.toggle('translate-x-4', active);
                    dot.classList.toggle('translate-x-0.5', !active);
                }
            });
        }
    });

    document.getElementById('btn-save-item').addEventListener('click', async () => {
        const id = document.getElementById('f-id').value;
        const fd = new FormData();
        fd.append('name_en', document.getElementById('f-name-en').value);
        fd.append('name_ar', document.getElementById('f-name-ar').value);
        fd.append('type', document.getElementById('f-type').value);
        fd.append('size', document.getElementById('f-size').value);
        fd.append('description_en', document.getElementById('f-description-en').value);
        fd.append('description_ar', document.getElementById('f-description-ar').value);
        fd.append('unit_cost', document.getElementById('f-unit-cost').value);
        fd.append('currency', document.getElementById('f-currency').value);
        if (!document.getElementById('f-unlimited-stock').checked) {
            fd.append('stock_available', document.getElementById('f-stock').value || '0');
        }
        fd.append('sort_order', document.getElementById('f-sort-order').value || '0');
        fd.append('is_active', document.getElementById('f-is-active').checked ? '1' : '0');
        const imageFile = document.getElementById('f-image').files[0];
        if (imageFile) fd.append('image', imageFile);

        let url = storeUrl;
        if (id) {
            fd.append('_method', 'PUT');
            url = `{{ url('admin/packaging/catalog') }}/${id}`;
        }

        const errEl = document.getElementById('form-error');
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (res.ok && data.success !== false) {
                closeModal('item-modal');
                window.reloadDataTable && window.reloadDataTable('packaging-catalog-table');
                window.Toast && window.Toast.success(data.message || 'Saved.');
            } else {
                errEl.textContent = data.message ?? Object.values(data.errors ?? {}).flat().join(' ');
                errEl.classList.remove('hidden');
            }
        } catch (e) {
            errEl.textContent = 'Something went wrong.';
            errEl.classList.remove('hidden');
        }
    });

    document.getElementById('btn-confirm-delete-item').addEventListener('click', async () => {
        const id = document.getElementById('delete-item-id').value;
        try {
            const res = await fetch(`{{ url('admin/packaging/catalog') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (res.ok && data.success !== false) {
                closeModal('delete-item-modal');
                window.reloadDataTable && window.reloadDataTable('packaging-catalog-table');
                window.Toast && window.Toast.success(data.message || 'Deleted.');
            } else {
                const errEl = document.getElementById('delete-item-error');
                errEl.textContent = data.message ?? 'Could not delete item.';
                errEl.classList.remove('hidden');
            }
        } catch (e) {
            const errEl = document.getElementById('delete-item-error');
            errEl.textContent = 'Something went wrong.';
            errEl.classList.remove('hidden');
        }
    });
})();
</script>
@endpush
@endsection
