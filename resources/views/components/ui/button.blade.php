@props(['href' => null, 'ghost' => false])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['button', 'button--ghost' => $ghost]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class(['button', 'button--ghost' => $ghost])->merge(['type' => 'button']) }}>{{ $slot }}</button>
@endif
