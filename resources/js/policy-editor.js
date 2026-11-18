import { Editor } from '@tiptap/core';
import { StarterKit } from '@tiptap/starter-kit';

const plainTextToHtml = (plainText) => {
    if (plainText.trim() === '') {
        return '<p></p>';
    }

    return plainText
        .replace(/\r\n/g, '\n')
        .split(/\n{2,}/)
        .map((paragraph) => {
            const element = document.createElement('p');

            paragraph.split('\n').forEach((line, index) => {
                if (index > 0) {
                    element.append(document.createElement('br'));
                }

                element.append(document.createTextNode(line));
            });

            return element.outerHTML;
        })
        .join('');
};

const normalizeLink = (value) => {
    const link = value.trim();

    if (link === '' || /^(https?:|mailto:|\/|#)/i.test(link)) {
        return link;
    }

    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(link)) {
        return `mailto:${link}`;
    }

    return `https://${link}`;
};

export const initializeRichTextEditor = (container) => {
    const input = container.querySelector('[data-rich-text-input]');
    const surface = container.querySelector('[data-rich-text-surface]');
    const toolbar = container.querySelector('[data-rich-text-toolbar]');
    const blockType = container.querySelector('[data-editor-block-type]');
    const initialContent = container.dataset.contentFormat === 'html'
        ? input.value
        : plainTextToHtml(input.value);

    const editor = new Editor({
        element: surface,
        content: initialContent,
        extensions: [
            StarterKit.configure({
                code: false,
                codeBlock: false,
                heading: { levels: [1, 2, 3] },
                link: {
                    autolink: true,
                    defaultProtocol: 'https',
                    HTMLAttributes: {
                        rel: 'noopener noreferrer',
                        target: '_blank',
                    },
                    openOnClick: false,
                },
                strike: false,
            }),
        ],
        editorProps: {
            attributes: {
                'aria-label': surface.getAttribute('aria-label'),
            },
        },
        onUpdate: ({ editor: updatedEditor }) => {
            input.value = updatedEditor.getHTML();
        },
    });

    const updateToolbar = () => {
        const activeCommands = {
            bold: editor.isActive('bold'),
            italic: editor.isActive('italic'),
            underline: editor.isActive('underline'),
            'bullet-list': editor.isActive('bulletList'),
            'ordered-list': editor.isActive('orderedList'),
            blockquote: editor.isActive('blockquote'),
            link: editor.isActive('link'),
        };

        toolbar.querySelectorAll('[data-editor-command]').forEach((button) => {
            if (Object.hasOwn(activeCommands, button.dataset.editorCommand)) {
                button.setAttribute('aria-pressed', activeCommands[button.dataset.editorCommand] ? 'true' : 'false');
            }
        });

        const activeHeading = [1, 2, 3].find((level) => editor.isActive('heading', { level }));
        blockType.value = activeHeading ? `heading-${activeHeading}` : 'paragraph';
    };

    toolbar.querySelectorAll('[data-editor-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const command = button.dataset.editorCommand;

            if (command === 'bold') editor.chain().focus().toggleBold().run();
            if (command === 'italic') editor.chain().focus().toggleItalic().run();
            if (command === 'underline') editor.chain().focus().toggleUnderline().run();
            if (command === 'bullet-list') editor.chain().focus().toggleBulletList().run();
            if (command === 'ordered-list') editor.chain().focus().toggleOrderedList().run();
            if (command === 'blockquote') editor.chain().focus().toggleBlockquote().run();
            if (command === 'horizontal-rule') editor.chain().focus().setHorizontalRule().run();
            if (command === 'clear-formatting') editor.chain().focus().unsetAllMarks().clearNodes().run();
            if (command === 'undo') editor.chain().focus().undo().run();
            if (command === 'redo') editor.chain().focus().redo().run();

            if (command === 'link') {
                const existingLink = editor.getAttributes('link').href ?? '';
                const requestedLink = window.prompt('Enter a web or email address:', existingLink);

                if (requestedLink !== null) {
                    const link = normalizeLink(requestedLink);

                    if (link === '') {
                        editor.chain().focus().extendMarkRange('link').unsetLink().run();
                    } else {
                        editor.chain().focus().extendMarkRange('link').setLink({ href: link }).run();
                    }
                }
            }

            updateToolbar();
        });
    });

    blockType.addEventListener('change', () => {
        if (blockType.value === 'paragraph') {
            editor.chain().focus().setParagraph().run();
        } else {
            editor.chain().focus().setHeading({ level: Number(blockType.value.replace('heading-', '')) }).run();
        }

        updateToolbar();
    });

    editor.on('selectionUpdate', updateToolbar);
    editor.on('transaction', updateToolbar);
    input.closest('form')?.addEventListener('submit', () => {
        input.value = editor.getHTML();
    });

    input.classList.add('hidden');
    surface.classList.remove('hidden');
    toolbar.classList.remove('hidden');
    container.querySelector('.rich-text-editor').classList.add('is-enhanced');
    input.value = editor.getHTML();
    updateToolbar();
};
