// Count-up for key figures, only when motion is allowed. Final value is always in the HTML.
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            observer.unobserve(entry.target);

            const el = entry.target;
            const text = el.textContent.trim();
            const match = text.match(/^(\D*)([\d,]+)(.*)$/);
            if (!match) return;

            const [, prefix, digits, suffix] = match;
            const target = parseInt(digits.replace(/,/g, ''), 10);
            const start = performance.now();
            const tick = (now) => {
                const progress = Math.min((now - start) / 1200, 1);
                const n = Math.round(target * progress);
                // Only add thousands separators if the figure had them (so a year stays "1859").
                el.textContent = prefix + (digits.includes(',') ? n.toLocaleString('en-US') : String(n)) + suffix;
                if (progress < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    }, { threshold: 0.4 });

    document.querySelectorAll('[data-count-up]').forEach((el) => observer.observe(el));
}
