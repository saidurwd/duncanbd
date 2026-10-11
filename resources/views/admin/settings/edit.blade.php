@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    @php($active = array_key_first($groups))
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')
        <ul class="nav nav-tabs mb-3" role="tablist">
            @foreach ($groups as $group => $definition)
                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link @if ($active === $group) active @endif" data-bs-toggle="tab" data-bs-target="#tab-{{ $group }}" role="tab">{{ $definition['label'] }}</button>
                </li>
            @endforeach
        </ul>
        <div class="tab-content">
            @foreach ($groups as $group => $definition)
                <div class="tab-pane fade @if ($active === $group) show active @endif" id="tab-{{ $group }}" role="tabpanel">
                    <div class="card"><div class="card-body">
                        @foreach ($definition['fields'] as $key => $field)
                            @php($id = "$group-$key")
                            <div class="mb-3">
                                <label for="{{ $id }}" class="form-label">{{ $field['label'] }}</label>
                                @if ($field['type'] === 'textarea')
                                    <textarea id="{{ $id }}" name="{{ $group }}[{{ $key }}]" rows="3" class="form-control @error("$group.$key") is-invalid @enderror">{{ old("$group.$key", $values[$group][$key] ?? '') }}</textarea>
                                @else
                                    <input id="{{ $id }}" type="{{ $field['type'] }}" name="{{ $group }}[{{ $key }}]"
                                        value="{{ ($field['secret'] ?? false) ? '' : old("$group.$key", $values[$group][$key] ?? '') }}"
                                        @if ($field['secret'] ?? false) autocomplete="new-password" placeholder="{{ isset($values[$group][$key]) ? '•••••••• (set)' : '' }}" @endif
                                        class="form-control @error("$group.$key") is-invalid @enderror">
                                @endif
                                @error("$group.$key")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                @isset($field['help'])<div class="form-text">{{ $field['help'] }}</div>@endisset
                            </div>
                        @endforeach
                    </div></div>
                </div>
            @endforeach
        </div>
        <button type="submit" class="btn btn-primary">Save settings</button>
    </form>
@endsection
