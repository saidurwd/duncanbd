// Pages are cached for guests and carry no session, so the newsletter band gets its CSRF token and
// time-trap value from a no-store endpoint the first time the visitor engages with the form.
document.querySelectorAll('[data-newsletter]').forEach((form) => {
    let loading = null;

    const load = () => {
        loading ??= fetch('/forms/token', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((r) => r.json())
            .then(({ csrf, ts }) => {
                form.querySelector('[data-form-csrf]').value = csrf;
                form.querySelector('[data-form-ts]').value = ts;
            });
        return loading;
    };

    form.addEventListener('focusin', load, { once: true });
    const status = form.parentElement.querySelector('[data-newsletter-status]');

    // Submit in place: the page is cached and session-less, so a normal redirect could not show the result.
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        status.textContent = 'Sending…';
        try {
            await load();
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[data-form-csrf]').value },
                body: new FormData(form),
                credentials: 'same-origin',
            });
            const body = await response.json().catch(() => ({}));
            const errors = body.errors ? Object.values(body.errors).flat().join(' ') : null;
            status.textContent = response.ok ? body.message : (errors || body.message || 'Sorry, something went wrong. Please try again.');
            if (response.ok) form.reset();
        } catch {
            status.textContent = 'Sorry, something went wrong. Please try again.';
        }
    });
});

// Move keyboard focus to the error summary after a failed submission.
document.querySelector('[data-error-summary]')?.focus();
