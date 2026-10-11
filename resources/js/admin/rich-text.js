// Rich-text editor (Tiptap, MIT). Produces only the HTML the server's allow-list keeps:
// h2-h4, p, br, strong, em, u, blockquote, ul, ol, li, a. The server still sanitises everything on save.
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';

const SAFE_URL = /^(https?:\/\/|mailto:|tel:|\/(?!\/)|#)/i;

const BUTTONS = [
    { label: 'Bold', text: 'B', cls: 'fw-bold', run: (e) => e.chain().focus().toggleBold().run(), active: (e) => e.isActive('bold') },
    { label: 'Italic', text: 'I', cls: 'fst-italic', run: (e) => e.chain().focus().toggleItalic().run(), active: (e) => e.isActive('italic') },
    { label: 'Underline', text: 'U', cls: 'text-decoration-underline', run: (e) => e.chain().focus().toggleUnderline().run(), active: (e) => e.isActive('underline') },
    { sep: true },
    { label: 'Heading 2', text: 'H2', run: (e) => e.chain().focus().toggleHeading({ level: 2 }).run(), active: (e) => e.isActive('heading', { level: 2 }) },
    { label: 'Heading 3', text: 'H3', run: (e) => e.chain().focus().toggleHeading({ level: 3 }).run(), active: (e) => e.isActive('heading', { level: 3 }) },
    { label: 'Heading 4', text: 'H4', run: (e) => e.chain().focus().toggleHeading({ level: 4 }).run(), active: (e) => e.isActive('heading', { level: 4 }) },
    { sep: true },
    { label: 'Bulleted list', text: '• List', run: (e) => e.chain().focus().toggleBulletList().run(), active: (e) => e.isActive('bulletList') },
    { label: 'Numbered list', text: '1. List', run: (e) => e.chain().focus().toggleOrderedList().run(), active: (e) => e.isActive('orderedList') },
    { label: 'Quote', text: '“ Quote', run: (e) => e.chain().focus().toggleBlockquote().run(), active: (e) => e.isActive('blockquote') },
    { sep: true },
    {
        label: 'Link', text: 'Link', active: (e) => e.isActive('link'),
        run: (e) => {
            const current = e.getAttributes('link').href || '';
            const url = window.prompt('Link address (https://…, /page-path, mailto:…). Leave empty to remove the link.', current);
            if (url === null) return;
            if (url.trim() === '') return e.chain().focus().extendMarkRange('link').unsetLink().run();
            if (!SAFE_URL.test(url.trim())) return window.alert('Only https://, mailto:, tel: and site-relative links are allowed.');
            e.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
        },
    },
    { sep: true },
    { label: 'Undo', text: '↶', run: (e) => e.chain().focus().undo().run() },
    { label: 'Redo', text: '↷', run: (e) => e.chain().focus().redo().run() },
    { label: 'Clear formatting', text: 'Clear', run: (e) => e.chain().focus().clearNodes().unsetAllMarks().run() },
];

/** Builds the editor UI. `onChange(html)` fires on every edit; returns { element, editor }. */
export function createRichText({ value = '', labelledBy = null, onChange = () => {} } = {}) {
    const wrap = document.createElement('div');
    wrap.className = 'rte border rounded';

    const toolbar = document.createElement('div');
    toolbar.className = 'rte-toolbar d-flex flex-wrap gap-1 p-1 border-bottom bg-body-tertiary';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Text formatting');

    const surface = document.createElement('div');
    surface.className = 'rte-surface p-2';

    wrap.append(toolbar, surface);

    const editor = new Editor({
        element: surface,
        injectCSS: false, // the CSP forbids injected <style>; styles live in the admin stylesheet
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: { openOnClick: false, autolink: true, protocols: ['mailto', 'tel'], HTMLAttributes: { rel: 'noopener', target: null } },
                strike: false, code: false, codeBlock: false, horizontalRule: false,
            }),
        ],
        content: value || '',
        editorProps: {
            attributes: { class: 'rte-content prose', role: 'textbox', 'aria-multiline': 'true', ...(labelledBy ? { 'aria-labelledby': labelledBy } : {}) },
        },
        onUpdate: ({ editor: e }) => { onChange(e.isEmpty ? '' : e.getHTML()); refresh(); },
        onSelectionUpdate: () => refresh(),
    });

    const buttons = [];
    for (const def of BUTTONS) {
        if (def.sep) {
            const s = document.createElement('span');
            s.className = 'rte-sep';
            s.setAttribute('aria-hidden', 'true');
            toolbar.append(s);
            continue;
        }
        const b = document.createElement('button');
        b.type = 'button';
        b.className = `btn btn-sm btn-outline-secondary ${def.cls || ''}`.trim();
        b.textContent = def.text;
        b.setAttribute('aria-label', def.label);
        b.title = def.label;
        if (def.active) b.setAttribute('aria-pressed', 'false');
        b.addEventListener('mousedown', (ev) => ev.preventDefault()); // keep the selection in the editor
        b.addEventListener('click', () => { def.run(editor); refresh(); });
        buttons.push([b, def]);
        toolbar.append(b);
    }

    function refresh() {
        for (const [b, def] of buttons) {
            if (def.active) {
                const on = def.active(editor);
                b.setAttribute('aria-pressed', String(on));
                b.classList.toggle('active', on);
            }
        }
    }

    return { element: wrap, editor };
}

/** Upgrades <textarea data-rich-text> in place; the textarea stays in the form (hidden) and carries the HTML. */
export function enhanceTextarea(textarea) {
    if (textarea.dataset.richReady) return;
    textarea.dataset.richReady = '1';

    const label = document.querySelector(`label[for="${textarea.id}"]`);
    if (label && !label.id) label.id = `${textarea.id}-label`;

    const { element, editor } = createRichText({
        value: textarea.value,
        labelledBy: label?.id,
        onChange: (html) => { textarea.value = html; },
    });

    textarea.hidden = true;
    textarea.after(element);
    textarea.form?.addEventListener('submit', () => { textarea.value = editor.isEmpty ? '' : editor.getHTML(); });
}

document.querySelectorAll('textarea[data-rich-text]').forEach(enhanceTextarea);
