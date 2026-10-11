// Hero slider: max 5 slides, pause control (WCAG 2.2.2), no auto-advance under reduced motion, >= 6s per slide.
document.querySelectorAll('[data-hero-slider]').forEach((root) => {
    const slides = [...root.querySelectorAll('[data-slide]')];
    const button = root.querySelector('[data-slider-pause]');
    if (slides.length < 2) {
        button?.remove();
        return;
    }

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let index = 0;
    let timer = null;

    const show = (i) => {
        slides[index].classList.remove('is-active');
        index = (i + slides.length) % slides.length;
        slides[index].classList.add('is-active');
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
    // Keyboard users tabbing into the slides pause it, but not when they land on the pause button itself.
    root.addEventListener('focusin', (event) => timer && event.target !== button && pause());

    reduced ? pause() : play();
});
