{{-- On cached pages: no CSRF token or time-trap value here. forms.js fetches fresh ones when the visitor focuses the field. --}}
<section class="newsletter-band" aria-labelledby="nl-heading">
    <div class="container newsletter-band__inner">
        <div>
            <h2 id="nl-heading">Receive news &amp; updates</h2>
            <p>Reports, announcements and stories from our estates, delivered to your inbox.</p>
        </div>
        <div>
            <form method="POST" action="{{ route('newsletter.subscribe') }}" data-newsletter>
                <input type="hidden" name="_token" value="" data-form-csrf>
                <input type="hidden" name="_ts" value="" data-form-ts>
                <div class="hp-field" aria-hidden="true"><label>Leave this field empty <input type="text" name="{{ App\Support\FormGuard::HONEYPOT }}" tabindex="-1" autocomplete="off"></label></div>
                <div class="newsletter-band__row">
                    <label for="nl-email" class="visually-hidden">Email address</label>
                    <input id="nl-email" type="email" name="email" placeholder="Your email address" required autocomplete="email">
                    <button class="button">Subscribe</button>
                </div>
                <label class="newsletter-consent"><input type="checkbox" name="consent" value="1" required><span> I agree to receive news and updates and have read the <a href="/privacy-notice">privacy notice</a>.</span></label>
            </form>
            <p class="newsletter-status" role="status" aria-live="polite" data-newsletter-status></p>
            <noscript><p><a href="{{ route('newsletter') }}">Subscribe on our sign-up page</a></p></noscript>
        </div>
    </div>
</section>
