// Full-screen "Explore" overlay: focus trap, Esc closes, aria-expanded, scroll lock.
const toggle = document.querySelector('[data-explore-toggle]');
const menu = document.querySelector('[data-explore-menu]');

if (toggle && menu) {
    const focusable = () => [...menu.querySelectorAll('a[href], button:not([disabled])')];

    const open = () => {
        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        document.body.classList.add('menu-open');
        (menu.querySelector('[data-explore-close]') || focusable()[0])?.focus();
    };

    const close = () => {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('menu-open');
        toggle.focus();
    };

    toggle.addEventListener('click', open);
    menu.querySelector('[data-explore-close]')?.addEventListener('click', close);

    menu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        } else if (event.key === 'Tab') {
            const items = focusable();
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
}
