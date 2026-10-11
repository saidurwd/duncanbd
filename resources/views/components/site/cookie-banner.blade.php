@props(['text'])
<div class="cookie-banner" data-cookie-banner role="region" aria-label="Cookie consent" hidden>
    <div class="container">
        <p>{{ $text }}</p>
        <div class="cookie-banner__actions">
            <button type="button" class="button" data-consent-accept>Accept all</button>
            <button type="button" class="button button--ghost" data-consent-reject>Reject non-essential</button>
        </div>
    </div>
</div>
