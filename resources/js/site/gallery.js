// Keyboard-accessible lightbox using the native <dialog> (focus trap + Esc come for free).
document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const dialog = gallery.querySelector('dialog');
    const image = dialog?.querySelector('img');
    const caption = dialog?.querySelector('[data-caption]');
    if (!dialog || !image) return;

    gallery.querySelectorAll('[data-full]').forEach((button) => {
        button.addEventListener('click', () => {
            image.src = button.dataset.full;
            image.alt = button.dataset.alt || '';
            if (caption) caption.textContent = button.dataset.alt || '';
            dialog.showModal();
        });
    });

    dialog.querySelector('[data-close]')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
});
