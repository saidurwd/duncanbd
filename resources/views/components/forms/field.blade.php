@props(['name', 'label', 'type' => 'text', 'required' => false, 'hint' => null])
<div class="field">
    <label for="f-{{ $name }}">{{ $label }}@if ($required) <span aria-hidden="true">*</span><span class="visually-hidden">(required)</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="f-{{ $name }}" name="{{ $name }}" rows="6" @required($required) @error($name) aria-invalid="true" aria-describedby="e-{{ $name }}" @enderror {{ $attributes }}>{{ old($name) }}</textarea>
    @else
        <input id="f-{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'file' ? '' : old($name) }}" @required($required) @error($name) aria-invalid="true" aria-describedby="e-{{ $name }}" @enderror {{ $attributes }}>
    @endif
    @if ($hint)<p class="meta">{{ $hint }}</p>@endif
    @error($name)<p class="field-error" id="e-{{ $name }}">{{ $message }}</p>@enderror
</div>
