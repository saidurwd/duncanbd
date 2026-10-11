@extends('layouts.admin')

@section('title', ($record->exists ? 'Edit ' : 'Add ').$def->label)

@section('content')
    <form method="POST" action="{{ $record->exists ? route('admin.content.update', [$def->key, $record->id]) : route('admin.content.store', $def->key) }}" class="row g-3">
        @csrf
        @if ($record->exists) @method('PUT') @endif
        @foreach ($def->fields as $key => $field)
            @php
                $value = old($key, $record->$key);
                if ($field['type'] === 'lines' && is_array($value)) { $value = implode("\n", $value); }
                if ($field['type'] === 'pairs' && is_array($value)) { $value = collect($value)->map(fn ($p) => $p['label'].' | '.$p['value'])->implode("\n"); }
                if ($key === 'is_active' && ! $record->exists) { $value = true; }
            @endphp
            <div class="@if (in_array($field['type'], ['richtext', 'textarea', 'lines', 'pairs'])) col-12 @else col-md-6 @endif">
                @if ($field['type'] === 'boolean')
                    <input type="hidden" name="{{ $key }}" value="0">
                    <div class="form-check"><input id="f-{{ $key }}" type="checkbox" name="{{ $key }}" value="1" class="form-check-input" @checked($value)><label for="f-{{ $key }}" class="form-check-label">{{ $field['label'] }}</label></div>
                @else
                    <label for="f-{{ $key }}" class="form-label">{{ $field['label'] }}</label>
                    @if (in_array($field['type'], ['textarea', 'richtext', 'lines', 'pairs']))
                        <textarea id="f-{{ $key }}" name="{{ $key }}" rows="{{ $field['type'] === 'richtext' ? 10 : 4 }}" class="form-control @error($key) is-invalid @enderror" @if ($field['type'] === 'richtext') data-rich-text @endif>{{ $value }}</textarea>
                    @elseif ($field['type'] === 'select')
                        <select id="f-{{ $key }}" name="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
                            @foreach ($field['options']() as $optValue => $optLabel)<option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>@endforeach
                        </select>
                    @else
                        <input id="f-{{ $key }}" name="{{ $key }}" type="{{ in_array($field['type'], ['number', 'media']) ? 'number' : ($field['type'] === 'url' ? 'url' : 'text') }}" @if ($field['type'] === 'number') step="any" @endif value="{{ $value }}" class="form-control @error($key) is-invalid @enderror">
                    @endif
                    @error($key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @endif
            </div>
        @endforeach
        <div class="col-12">
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.content.index', $def->key) }}" class="btn btn-link">Back</a>
        </div>
    </form>
    @if ($record->exists)
        <form method="POST" action="{{ route('admin.content.destroy', [$def->key, $record->id]) }}" class="mt-4">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
    @endif
@endsection
