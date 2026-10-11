// Row add/remove/renumber for the menu editor (plain DOM, CSP-safe).
document.querySelectorAll('[data-menu-editor]').forEach((form) => {
    const body = form.querySelector('[data-rows]');
    const template = form.querySelector('[data-row-template]');
    let counter = Date.now();

    const renumber = () => body.querySelectorAll('tr').forEach((tr, i) => {
        tr.querySelector('[data-num]').textContent = i + 1;
    });

    form.querySelector('[data-add-row]').addEventListener('click', () => {
        const html = template.innerHTML.replaceAll('__INDEX__', String(counter++));
        body.insertAdjacentHTML('beforeend', html);
        renumber();
    });

    body.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-row]');
        if (button) {
            button.closest('tr').remove();
            renumber();
        }
    });
});
