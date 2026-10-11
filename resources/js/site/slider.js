// Hero slider: arrows, dots, pause control (WCAG 2.2.2), no auto-advance under reduced motion, 6 s per slide.
document.querySelectorAll('[data-hero-slider]').forEach((root) => {
    const slides = [...root.querySelectorAll('[data-slide]')];
    const dots = [...root.querySelectorAll('[data-slider-dot]')];
    const button = root.querySelector('[data-slider-pause]');
    if (slides.length < 2) return;

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let index = 0;
    let timer = null;

    const show = (i) => {
        slides[index].classList.remove('is-active');
        dots[index]?.removeAttribute('aria-current');
        index = (i + slides.length) % slides.length;
        slides[index].classList.add('is-active');
        dots[index]?.setAttribute('aria-current', 'true');
    };
    const play = () => {
        timer = setInterval(() => show(index + 1), 6000);
        button.textContent = 'Pause slideshow';
        button.setAttribute('aria-pressed', 'false');
    };
    const pause = () => {
        clearInterval(timer);
        timer = null;
        button.textContent = 'Play slideshow';
        button.setAttribute('aria-pressed', 'true');
    };

    button.addEventListener('click', () => (timer ? pause() : play()));
    root.querySelector('[data-slider-prev]')?.addEventListener('click', () => { timer && pause(); show(index - 1); });
    root.querySelector('[data-slider-next]')?.addEventListener('click', () => { timer && pause(); show(index + 1); });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { timer && pause(); show(i); }));
    // Keyboard users tabbing into the slider pause it, but not when they land on the controls themselves.
    root.addEventListener('focusin', (event) => timer && !event.target.closest('button') && pause());

    reduced ? pause() : play();
});
