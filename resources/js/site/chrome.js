// Sticky-header shadow and the back-to-top button (CSSOM/class toggles only: CSP-safe).
const header = document.querySelector('[data-site-header]');
const toTop = document.querySelector('[data-back-to-top]');

const onScroll = () => {
    const y = window.scrollY;
    header?.classList.toggle('is-scrolled', y > 8);
    if (toTop) toTop.hidden = y < 600;
};

window.addEventListener('scroll', onScroll, { passive: true });
onScroll();

toTop?.addEventListener('click', (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    document.querySelector('.skip-link')?.focus({ preventScroll: true });
});
