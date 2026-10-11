{{-- Honeypot + time trap (+ Turnstile when configured). Rendered inside every public form.
     After a validation error the ORIGINAL timestamp is kept (old input), so fixing a field quickly is not mistaken for a bot. --}}
@php($guard = app(App\Support\FormGuard::class))
<div class="hp-field" aria-hidden="true">
    <label>Leave this field empty <input type="text" name="{{ App\Support\FormGuard::HONEYPOT }}" tabindex="-1" autocomplete="off"></label>
</div>
<input type="hidden" name="_ts" value="{{ old('_ts') ?: $guard->issueTimestamp() }}" data-form-ts>
@if ($guard->turnstileEnabled())
    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
