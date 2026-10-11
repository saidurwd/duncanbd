// Schema-driven page block editor. Plain DOM (no inline scripts / eval) so it works under the strict CSP.
// State is serialised into a hidden input as JSON; the server re-validates and sanitises everything.

const el = (tag, attrs = {}, children = []) => {
    const node = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) {
        if (k === 'class') node.className = v;
        else if (k.startsWith('on')) node.addEventListener(k.slice(2), v);
        else node.setAttribute(k, v);
    }
    [].concat(children).forEach((c) => node.append(c));
    return node;
};

let uid = 0;

function fieldInput(field, value, onChange) {
    const id = `be-${++uid}`;
    const common = { id, class: 'form-control form-control-sm' };
    let input;

    if (field.type === 'textarea' || field.type === 'richtext') {
        input = el('textarea', { ...common, rows: field.type === 'richtext' ? 8 : 3 });
        input.value = value ?? '';
    } else if (field.type === 'select') {
        input = el('select', { ...common, class: 'form-select form-select-sm' }, [
            el('option', { value: '' }, '—'),
            ...Object.entries(field.options).map(([v, l]) => el('option', { value: v }, l)),
        ]);
        input.value = value ?? '';
    } else {
        // URL fields accept site-relative paths too, which type=url would reject in the browser.
        const type = field.type === 'number' || field.type === 'media' ? 'number' : 'text';
        input = el('input', { ...common, type, ...(field.type === 'url' ? { placeholder: 'https://… or /page-path', inputmode: 'url' } : {}) });
        input.value = value ?? '';
    }
    input.addEventListener('input', () => onChange(input.value === '' ? null : input.value));

    const label = field.label + (field.type === 'media' ? ' (media ID)' : '') + (field.type === 'richtext' ? ' (HTML: h2–h4, p, lists, links, tables)' : '');

    return el('div', { class: 'mb-2' }, [
        el('label', { for: id, class: 'form-label small mb-1' }, label + (field.required ? ' *' : '')),
        input,
    ]);
}

function renderFields(fields, data, onChange) {
    return Object.entries(fields).map(([key, field]) => {
        if (field.type !== 'repeater') {
            return fieldInput(field, data[key], (v) => { data[key] = v; onChange(); });
        }

        data[key] = Array.isArray(data[key]) ? data[key] : [];
        const wrap = el('fieldset', { class: 'border rounded p-2 mb-2' }, [el('legend', { class: 'small float-none w-auto px-1' }, field.label)]);
        const rows = el('div');
        const draw = () => {
            rows.replaceChildren(...data[key].map((row, i) => el('div', { class: 'border-bottom pb-2 mb-2' }, [
                ...renderFields(field.fields, row, onChange),
                el('button', { type: 'button', class: 'btn btn-sm btn-outline-danger', onclick: () => { data[key].splice(i, 1); draw(); onChange(); } }, 'Remove'),
            ])));
        };
        draw();
        wrap.append(rows, el('button', {
            type: 'button', class: 'btn btn-sm btn-outline-secondary',
            onclick: () => { if (data[key].length < (field.limit ?? 50)) { data[key].push({}); draw(); onChange(); } },
        }, 'Add row'));

        return wrap;
    });
}

export function initBlockEditor(root) {
    const schema = JSON.parse(root.dataset.schema);
    const blocks = JSON.parse(root.dataset.initial || '[]');
    const hidden = root.querySelector('input[name="blocks_json"]');
    const list = root.querySelector('[data-block-list]');
    const picker = root.querySelector('[data-block-type]');

    const sync = () => { hidden.value = JSON.stringify(blocks); };

    const draw = () => {
        list.replaceChildren(...blocks.map((block, i) => {
            block.data = block.data || {};
            const def = schema[block.type];
            const move = (d) => () => { const j = i + d; if (j >= 0 && j < blocks.length) { [blocks[i], blocks[j]] = [blocks[j], blocks[i]]; draw(); sync(); } };

            return el('div', { class: 'card mb-3' }, [
                el('div', { class: 'card-header d-flex justify-content-between align-items-center' }, [
                    el('strong', {}, `${i + 1}. ${def.label}`),
                    el('div', { class: 'btn-group btn-group-sm' }, [
                        el('button', { type: 'button', class: 'btn btn-outline-secondary', 'aria-label': 'Move up', onclick: move(-1) }, '↑'),
                        el('button', { type: 'button', class: 'btn btn-outline-secondary', 'aria-label': 'Move down', onclick: move(1) }, '↓'),
                        el('button', { type: 'button', class: 'btn btn-outline-danger', onclick: () => { blocks.splice(i, 1); draw(); sync(); } }, 'Remove'),
                    ]),
                ]),
                el('div', { class: 'card-body' }, renderFields(def.fields, block.data, sync)),
            ]);
        }));
    };

    root.querySelector('[data-add-block]').addEventListener('click', () => {
        if (picker.value && blocks.length < 60) { blocks.push({ type: picker.value, data: {} }); draw(); sync(); }
    });

    draw();
    sync();
}

document.querySelectorAll('[data-block-editor]').forEach(initBlockEditor);
