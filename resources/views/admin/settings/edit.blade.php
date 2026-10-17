@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
    <div class="card mb-4">
        <div class="card-header"><h2 class="card-title">Branding: logo and favicon</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.branding') }}" enctype="multipart/form-data" class="row g-4">
                @csrf
                @foreach ($branding['slots'] as $slot => $label)
                    @php($current = $branding['current'][$slot] ?? null)
                    <div class="col-lg-3">
                        <label for="b-{{ $slot }}" class="form-label fw-semibold">{{ $label }}</label>
                        @if ($current)
                            <div class="branding-preview {{ $slot === 'logo' ? 'branding-preview--dark' : '' }} mb-2"><img src="{{ $current['path'] }}" alt="Current {{ strtolower($label) }}"></div>
                        @else
                            <p class="text-muted small">Not set.</p>
                        @endif
                        <input id="b-{{ $slot }}" type="file" name="{{ $slot }}" class="form-control @error($slot) is-invalid @enderror" accept="{{ $slot === 'favicon' ? 'image/png' : 'image/png,image/jpeg,image/webp' }}">
                        @error($slot)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @if ($current)
                            <div class="form-check mt-2"><input id="rm-{{ $slot }}" type="checkbox" name="remove[]" value="{{ $slot }}" class="form-check-input"><label for="rm-{{ $slot }}" class="form-check-label">Remove</label></div>
                        @endif
                    </div>
                @endforeach
                <div class="col-12">
                    <p class="form-text mb-2">PNG with a transparent background gives the best result (JPEG and WebP also work; SVG is not accepted). The header and footer are dark blue, so supply a <strong>white or light</strong> version of the logo there. Maximum 2 MB; the favicon must be a square PNG. The share image (JPEG or PNG, up to 3 MB) is the picture Facebook, LinkedIn, WhatsApp and X show when someone shares a page; individual pages and news stories can use their own.</p>
                    <button class="btn btn-primary">Save branding</button>
                </div>
            </form>
        </div>
    </div>

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
