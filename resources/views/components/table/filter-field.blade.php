{{--
Filter field partial for the datatable filter panel.
Variables: $filter (array), $tableId (string)

$filter shape:
type: 'text' | 'select' | 'date' | 'date_range' | 'number_range' | 'async_select'
name: string (request input name)
label: string
placeholder: string (optional)
options: array (for 'select' type, keyed value => label)
url: string (for 'async_select' type, AJAX search endpoint)
param: string (for 'async_select' type, query param name, default 'q')
min_length: int (for 'async_select' type, default 2)
--}}
@php
    $type = $filter['type'] ?? 'text';
    $name = $filter['name'] ?? '';
    $label = $filter['label'] ?? '';
    $placeholder = $filter['placeholder'] ?? '';
    $options = $filter['options'] ?? [];
@endphp

<div class="space-y-1">
    <label for="{{ $tableId }}-filter-{{ $name }}"
        class="block text-xs font-medium text-gray-600 uppercase tracking-wide">
        {{ $label }}
    </label>

    @if($type === 'text')
        <input type="text" id="{{ $tableId }}-filter-{{ $name }}" name="{{ $name }}"
            placeholder="{{ $placeholder ?: 'Filter…' }}" class="form-input text-sm">

    @elseif($type === 'select')
        <select id="{{ $tableId }}-filter-{{ $name }}" name="{{ $name }}" class="form-select text-sm">
            <option value="">{{ $placeholder ?: __('admin.all') }}</option>
            @foreach($options as $val => $lbl)
                <option value="{{ $val }}">{{ $lbl }}</option>
            @endforeach
        </select>

    @elseif($type === 'date')
        <input type="text" id="{{ $tableId }}-filter-{{ $name }}" name="{{ $name }}" placeholder="YYYY-MM-DD" readonly
            data-flatpickr class="form-input text-sm cursor-pointer">

    @elseif($type === 'date_range')
        <div class="flex gap-2">
            <input type="text" id="{{ $tableId }}-filter-{{ $name }}_from" name="{{ $name }}_from" placeholder="From"
                readonly data-flatpickr class="form-input text-sm cursor-pointer w-full">
            <input type="text" id="{{ $tableId }}-filter-{{ $name }}_to" name="{{ $name }}_to" placeholder="To" readonly
                data-flatpickr class="form-input text-sm cursor-pointer w-full">
        </div>

    @elseif($type === 'number_range')
        <div class="flex gap-2">
            <input type="number" id="{{ $tableId }}-filter-{{ $name }}_min" name="{{ $name }}_min"
                placeholder="{{ $filter['min_placeholder'] ?? 'Min' }}" class="form-input text-sm w-full"
                step="{{ $filter['step'] ?? 'any' }}">
            <input type="number" id="{{ $tableId }}-filter-{{ $name }}_max" name="{{ $name }}_max"
                placeholder="{{ $filter['max_placeholder'] ?? 'Max' }}" class="form-input text-sm w-full"
                step="{{ $filter['step'] ?? 'any' }}">
        </div>

    @elseif($type === 'async_select')
        <select id="{{ $tableId }}-filter-{{ $name }}" name="{{ $name }}" data-async-select data-config='{{ json_encode([
            "url" => $filter["url"],
            "param" => $filter["param"] ?? "q",
            "minLength" => $filter["min_length"] ?? 2,
            "delay" => 300,
        ]) }}' class="form-select text-sm" placeholder="{{ $placeholder ?: 'Search…' }}">
        </select>
    @endif
</div>